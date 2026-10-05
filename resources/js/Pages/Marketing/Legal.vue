<script setup>
import { computed } from 'vue';
import Seo from '@/Components/Marketing/Seo.vue';

const props = defineProps({
    seo: { type: Object, required: true },
    document: { type: String, required: true },
});

const documents = {
    privacy: {
        title: 'Privacy policy',
        updated: 'October 1, 2026',
        sections: [
            ['Information we collect', 'We collect the account details you provide (name, email, password hash), the content you create in your workspaces, and basic technical data such as IP address and browser type for security and diagnostics.'],
            ['How we use information', 'We use your information to provide and secure the service, send transactional emails such as invitations and notifications, and improve the product. We do not sell personal data.'],
            ['Workspace data', 'Content in a workspace belongs to that workspace. It is isolated from other workspaces and only accessible to its members according to their roles.'],
            ['Retention & deletion', 'You can delete a workspace at any time from Settings → Danger Zone. Deleted workspace data is removed from our primary systems within 30 days.'],
            ['Contact', 'Questions about privacy can be sent to privacy@orbitops.app.'],
        ],
    },
    terms: {
        title: 'Terms of service',
        updated: 'October 1, 2026',
        sections: [
            ['Using OrbitOps', 'You may use OrbitOps to manage your business operations in accordance with these terms and applicable law. You are responsible for activity in workspaces you own.'],
            ['Accounts', 'Keep your credentials secure and enable two-factor authentication. Notify us promptly of any unauthorized access.'],
            ['Your content', 'You retain ownership of the content you add. You grant us the limited rights needed to host and display it for you and the people you share it with.'],
            ['Plans & billing', 'Paid plans renew automatically until cancelled. Prices shown on the website are illustrative during early access.'],
            ['Changes', 'We may update these terms. Material changes will be communicated in-app or by email before they take effect.'],
        ],
    },
};

const doc = computed(() => documents[props.document] ?? documents.terms);
</script>

<template>
    <Seo :seo="seo" />
    <article class="mx-auto max-w-3xl px-5 pt-32 pb-24 sm:px-8 sm:pt-40">
        <p class="text-eyebrow text-accent-text uppercase">Legal</p>
        <h1 class="mt-4 text-h1 text-ink">{{ doc.title }}</h1>
        <p class="mt-3 text-small text-ink-3">Last updated {{ doc.updated }}</p>
        <section v-for="[heading, body] in doc.sections" :key="heading" class="mt-10">
            <h2 class="text-h3 text-ink">{{ heading }}</h2>
            <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-2">{{ body }}</p>
        </section>
    </article>
</template>
