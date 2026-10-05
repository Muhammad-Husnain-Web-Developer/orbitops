export function debounce(fn, wait = 250) {
    let timer;

    const debounced = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };

    debounced.cancel = () => clearTimeout(timer);

    return debounced;
}
