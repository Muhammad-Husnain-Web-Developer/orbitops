import { onBeforeUnmount, onMounted } from 'vue';

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);

export const modKey = isMac ? '⌘' : 'Ctrl';

function isTyping(event) {
    const el = event.target;

    return el instanceof HTMLElement && (el.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName));
}

/**
 * Register global shortcuts for a component's lifetime.
 * Keys: "mod+k", "/", "g d" style sequences are kept out on purpose — single chords only.
 */
export function useHotkeys(bindings) {
    function handler(event) {
        for (const [combo, callback] of Object.entries(bindings)) {
            const parts = combo.toLowerCase().split('+');
            const key = parts.pop();
            const needsMod = parts.includes('mod');
            const needsShift = parts.includes('shift');
            const modPressed = isMac ? event.metaKey : event.ctrlKey;

            if (event.key.toLowerCase() !== key || needsMod !== modPressed || needsShift !== event.shiftKey) {
                continue;
            }

            if (!needsMod && isTyping(event)) {
                continue;
            }

            event.preventDefault();
            callback(event);

            return;
        }
    }

    onMounted(() => window.addEventListener('keydown', handler));
    onBeforeUnmount(() => window.removeEventListener('keydown', handler));
}
