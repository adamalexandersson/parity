import { createElement, Fragment } from '@wordpress/element';
import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { SchemaRenderer } from './schemaRenderer.js';
import { resolveNamedSlotNames } from '../schema/slots.js';
import { isVoidElement } from '../support/voidElements.js';
import { isSvgTag } from '../support/reactAttributeMap.js';
import { normalizeDomAttributes } from '../support/domAttributes.js';
import { isDebugMode, schemaErrorPanelProps } from '../support/schemaError.js';
import { CHEVRON_DOWN_PATH, chevronDownSvgProps } from '../support/componentRefIcons.js';
import {
    hasStructureChildren,
    shouldRenderDefaultSlot,
    shouldSkipNamedSlotNode,
} from './slotResolver.js';

function renderComponentRef(element, props = {}) {
    if (! element.componentRef) {
        return null;
    }

    const mappedIcon = element.componentMapping && element.componentMappingKey
        ? element.componentMapping[props[element.componentMappingKey]] ?? null
        : null;

    if (element.componentRef.includes('chevron-down')) {
        return createElement(
            'svg',
            {
                ...chevronDownSvgProps(),
                'data-sprout-component': element.componentRef,
                'data-sprout-icon': mappedIcon || undefined,
            },
            createElement('path', CHEVRON_DOWN_PATH),
        );
    }

    return createElement('span', {
        'data-sprout-component': element.componentRef,
        'data-sprout-icon': mappedIcon || undefined,
        className: element.componentClass || undefined,
        'aria-hidden': true,
    });
}

export function renderStructure({
    structure,
    slotElement,
    slotConfig = {},
    props = {},
    setAttributes = null,
    namedSlotProps = {},
    registry = {},
    svgParent = false,
}) {
    const renderElement = (element, key, structureChildren = null, parentPath = null, parentIsSvg = false) => {
        const path = parentPath ? `${parentPath}.${key}` : key;
        const Tag = element.tag ?? 'div';
        const slotName = element.slot?.name ?? null;
        const slotContent = slotName ? namedSlotProps[slotName] : null;
        const svg = parentIsSvg || isSvgTag(Tag);

        if (shouldSkipNamedSlotNode(element, namedSlotProps)) {
            return null;
        }

        const children = structureChildren ?? element.children ?? {};
        const renderDefaultSlot = shouldRenderDefaultSlot(element, path, key, slotElement);
        const elementAttributes = normalizeDomAttributes(element.attributes ?? {}, {
            tag: Tag,
            svgParent: svg,
        });

        const componentPlaceholder = renderComponentRef(element, props);

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

                {componentPlaceholder}

                {hasStructureChildren(children) && Object.keys(children).map((childKey) => renderElement(
                    children[childKey],
                    childKey,
                    children[childKey].children,
                    path,
                    svg,
                ))}
            </>
        );

        if (element.fragment) {
            return createElement(Fragment, { key }, inner);
        }

        if (isVoidElement(Tag)) {
            return createElement(Tag, { key, ...elementAttributes });
        }

        return createElement(Tag, { key, ...elementAttributes }, inner);
    };

    return Object.entries(structure).map(([key, element]) => renderElement(
        element,
        key,
        element.children,
        null,
        svgParent,
    ));
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
        try {
            const config = window.sprout?.config?.[componentName] ?? {};
            const namedSlotNames = resolveNamedSlotNames(config);

            const structureProps = Object.fromEntries(
                Object.entries(componentProps).filter(([key]) => !namedSlotNames.includes(key) && key !== 'blockProps')
            );

            const renderer = new SchemaRenderer(componentName, structureProps, config);
            const componentAttributes = renderer.renderComponentAttributes();
            const structure = renderer.renderStructure();
            const RootTag = config.tag ?? 'div';

            const mergedAttributes = normalizeDomAttributes(componentAttributes, {
                tag: RootTag,
                svgParent: isSvgTag(RootTag),
            });

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
                svgParent: isSvgTag(RootTag),
            });

            if (isVoidElement(RootTag)) {
                return createElement(RootTag, mergedAttributes);
            }

            return createElement(RootTag, mergedAttributes, renderedStructure);
        } catch (error) {
            if (isDebugMode()) {
                return createElement('div', schemaErrorPanelProps(error, componentName));
            }

            if (typeof console !== 'undefined' && console.error) {
                console.error(error);
            }

            return null;
        }
    }

    return SproutComponent;
}

export function registerComponent(name, registry = {}) {
    registry[name] = createComponent(name, registry);
    return registry[name];
}
