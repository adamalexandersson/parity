/**
 * Slot resolution rules shared with Sprout\Render\SlotResolver (PHP) and structure.blade.php.
 */

export function hasStructureChildren(children) {
    return Boolean(children && Object.keys(children).length > 0);
}

export function isSlotNode(element, path, key, slotElement) {
    const slot = element.slot ?? null;
    const isDefaultSlot = Boolean(slot?.default);

    return isDefaultSlot || path === slotElement || key === slotElement;
}

export function shouldRenderDefaultSlot(element, path, key, slotElement) {
    if (element.richText) {
        return false;
    }

    const children = element.children ?? {};

    return !hasStructureChildren(children) && isSlotNode(element, path, key, slotElement);
}

export function shouldSkipNamedSlotNode(element, namedSlotProps) {
    const slotName = element.slot?.name ?? null;

    if (!slotName) {
        return false;
    }

    return namedSlotProps[slotName] == null;
}

export function collectDefaultSlotTargets(structure, slotElement) {
    const targets = [];

    Object.entries(structure ?? {}).forEach(([key, element]) => {
        const path = element.path ?? key;

        if (shouldRenderDefaultSlot(element, path, key, slotElement)) {
            targets.push(path);
        }

        targets.push(...collectDefaultSlotTargets(element.children ?? {}, slotElement));
    });

    return targets;
}
