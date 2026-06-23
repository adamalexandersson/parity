import { createElement, Fragment } from '@wordpress/element';
import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { SchemaRenderer } from './schemaRenderer.js';
import { resolveNamedSlotNames } from '../schema/slots.js';
import {
    hasStructureChildren,
    shouldRenderDefaultSlot,
    shouldSkipNamedSlotNode,
} from './slotResolver.js';

export function renderStructure({
    structure,
    slotElement,
    slotConfig = {},
    props = {},
    setAttributes = null,
    namedSlotProps = {},
    registry = {},
}) {
    const renderElement = (element, key, structureChildren = null, parentPath = null) => {
        const path = parentPath ? `${parentPath}.${key}` : key;
        const Tag = element.tag ?? 'div';
        const slotName = element.slot?.name ?? null;
        const slotContent = slotName ? namedSlotProps[slotName] : null;

        if (shouldSkipNamedSlotNode(element, namedSlotProps)) {
            return null;
        }

        const children = structureChildren ?? element.children ?? {};
        const renderDefaultSlot = shouldRenderDefaultSlot(element, path, key, slotElement);

        const inner = (
            <>
                {element.richText && setAttributes ? (
                    <RichText
                        value={props[element.richText.prop] ?? ''}
                        onChange={(value) => setAttributes({ [element.richText.prop]: value })}
                        placeholder={element.richText.placeholder ?? ''}
                        allowedFormats={element.richText.allowedFormats ?? []}
                    />
                ) : renderDefaultSlot ? (
                    slotConfig.renderSlot ? slotConfig.renderSlot() : (
                        <InnerBlocks
                            template={slotConfig.template ?? []}
                            allowedBlocks={slotConfig.allowedBlocks ?? null}
                            templateLock={slotConfig.templateLock ?? null}
                        />
                    )
                ) : slotContent}

                {hasStructureChildren(children) && Object.keys(children).map((childKey) => renderElement(
                    children[childKey],
                    childKey,
                    children[childKey].children,
                    path,
                ))}
            </>
        );

        if (element.fragment) {
            return createElement(Fragment, { key }, inner);
        }

        return createElement(Tag, { key, ...element.attributes }, inner);
    };

    return Object.entries(structure).map(([key, element]) => renderElement(element, key, element.children));
}

export function createComponent(componentName, registry = {}) {
    function SproutComponent({
        children,
        editable,
        setAttributes,
        slotConfig = {},
        className: extraClassName,
        blockProps: injectedBlockProps,
        ...componentProps
    }) {
        const config = window.componentConfig?.[componentName] ?? {};
        const namedSlotNames = resolveNamedSlotNames(config);

        const structureProps = Object.fromEntries(
            Object.entries(componentProps).filter(([key]) => !namedSlotNames.includes(key) && key !== 'blockProps')
        );

        const renderer = new SchemaRenderer(componentName, structureProps, config);
        const componentAttributes = renderer.renderComponentAttributes();
        const structure = renderer.renderStructure();
        const RootTag = config.tag ?? 'div';

        const mergedAttributes = { ...componentAttributes };

        if (extraClassName) {
            mergedAttributes.className = [mergedAttributes.className, extraClassName].filter(Boolean).join(' ');
        }

        if (injectedBlockProps) {
            const { className: blockClassName, style: blockStyle, ...restBlockProps } = injectedBlockProps;
            Object.assign(mergedAttributes, restBlockProps);

            if (blockClassName) {
                mergedAttributes.className = [mergedAttributes.className, blockClassName].filter(Boolean).join(' ');
            }

            if (blockStyle) {
                mergedAttributes.style = { ...(mergedAttributes.style ?? {}), ...blockStyle };
            }
        }

        const slotConfigResolved = { ...slotConfig };

        if (children !== undefined && slotConfig.renderSlot === undefined) {
            slotConfigResolved.renderSlot = () => children;
        }

        const namedSlotProps = {};

        namedSlotNames.forEach((name) => {
            if (componentProps[name] !== undefined && componentProps[name] !== null) {
                namedSlotProps[name] = componentProps[name];
            }
        });

        const renderedStructure = renderStructure({
            structure,
            slotElement: config.defaultSlot ?? null,
            slotConfig: slotConfigResolved,
            props: structureProps,
            setAttributes: editable ? setAttributes : null,
            namedSlotProps,
            registry,
        });

        return createElement(RootTag, mergedAttributes, renderedStructure);
    }

    return SproutComponent;
}

export function registerComponent(name, registry = {}) {
    registry[name] = createComponent(name, registry);
    return registry[name];
}
