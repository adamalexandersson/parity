import { createElement, Fragment } from '@wordpress/element';
import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { SchemaRenderer } from './schemaRenderer.js';
import { resolveNamedSlotNames } from '../schema/slots.js';
import { isVoidElement } from '../support/voidElements.js';
import { isSvgTag } from '../support/reactAttributeMap.js';
import { normalizeDomAttributes } from '../support/domAttributes.js';
import { isDebugMode, schemaErrorPanelProps } from '../support/schemaError.js';
import { resolveIcon } from '../support/iconResolver.js';
import {
    hasStructureChildren,
    shouldRenderDefaultSlot,
    shouldSkipNamedSlotNode,
} from './slotResolver.js';

/**
 * Mirror PHP structure.blade.php nested `component` semantics.
 *
 * @param {Record<string, unknown>} element
 * @param {Record<string, unknown>} props
 * @param {Record<string, unknown>} registry
 * @returns {unknown|null}
 */
function renderComponentRef(element, props = {}, registry = {}) {
    const comp = element.component;

    if (! comp?.ref) {
        return null;
    }

    const ref = comp.ref;
    const mappingKey = comp.from;
    const mapping = comp.map;
    const className = comp.class;
    const nestedProps = {
        ...(comp.props ?? {}),
    };

    if (className) {
        nestedProps.className = [nestedProps.className, className]
            .filter(Boolean)
            .join(' ');
    }

    const hasMapping = Boolean(mapping && mappingKey);

    if (hasMapping) {
        const mappedValue = mapping[props[mappingKey]] ?? null;

        if (mappedValue == null || mappedValue === '') {
            return null;
        }

        const icon = resolveIcon(String(mappedValue), element);

        if (icon) {
            return icon;
        }

        const Nested = registry[ref];

        if (Nested) {
            return createElement(Nested, nestedProps);
        }

        return componentRefFallback(element, mappedValue);
    }

    const Nested = registry[ref];

    if (Nested) {
        return createElement(Nested, nestedProps);
    }

    const icon = resolveIcon(String(ref), element);

    if (icon) {
        return icon;
    }

    return componentRefFallback(element, null);
}

function componentRefFallback(element, mappedValue) {
    if (! isDebugMode()) {
        return null;
    }

    const comp = element.component ?? {};
    const ref = comp.ref;
    const label = mappedValue
        ? `${ref} → ${mappedValue}`
        : String(ref);

    return createElement('span', {
        'data-parity-component': ref,
        'data-parity-icon': mappedValue || undefined,
        className: comp.class || undefined,
        'aria-hidden': true,
        title: `[Parity] Unresolved component: ${label}`,
    }, `▾ ${label}`);
}

function renderStructure({
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

        const showComponentRef = ! element.richText && ! renderDefaultSlot && ! slotContent;
        const componentNode = showComponentRef
            ? renderComponentRef(element, props, registry)
            : null;

        const primaryContent = element.richText && setAttributes ? (
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
        ) : slotContent ? slotContent : componentNode;

        const inner = (
            <>
                {primaryContent}

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
    function ParityComponent({
        children,
        editable,
        setAttributes,
        slotConfig = {},
        className: extraClassName,
        blockProps: injectedBlockProps,
        ...componentProps
    }) {
        try {
            const config = window.parity?.config?.[componentName] ?? {};
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

    return ParityComponent;
}

export function registerComponent(name, component = null, registry = {}) {
    // Allow registerComponent(name, registry) for the previous two-arg shape.
    if (component !== null && typeof component === 'object' && ! component.$$typeof && ! component.prototype && typeof component !== 'function') {
        registry = component;
        component = null;
    }

    registry[name] = component ?? createComponent(name, registry);
    return registry[name];
}
