<script setup>
defineProps({
    caption: { type: String, required: true },
    labels: { type: Array, required: true },
    series: { type: Array, required: true },
    format: { type: Function, required: true },
});
</script>

<template>
    <table class="sr-only">
        <caption>{{ caption }}</caption>
        <thead>
            <tr>
                <th scope="col">Period</th>
                <th v-for="item in series" :key="item.key" scope="col">{{ item.name }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(label, index) in labels" :key="label">
                <th scope="row">{{ label }}</th>
                <td v-for="item in series" :key="item.key">{{ format(item.values[index] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>
</template>
