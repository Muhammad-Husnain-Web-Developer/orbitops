import { reactive } from 'vue';

export const palette = reactive({ open: false, mode: 'root' });

export function openPalette(mode = 'root') {
    palette.mode = mode;
    palette.open = true;
}

export function closePalette() {
    palette.open = false;
}
