<script setup>
import { ref } from 'vue';
import CtaSection from '@/Components/Marketing/CtaSection.vue';
import EngineeringSection from '@/Components/Marketing/EngineeringSection.vue';
import FeatureBento from '@/Components/Marketing/FeatureBento.vue';
import HeroSection from '@/Components/Marketing/HeroSection.vue';
import PricingTable from '@/Components/Marketing/PricingTable.vue';
import ProductShowcase from '@/Components/Marketing/ProductShowcase.vue';
import ScrollStory from '@/Components/Marketing/ScrollStory.vue';
import SectionHeading from '@/Components/Marketing/SectionHeading.vue';
import Seo from '@/Components/Marketing/Seo.vue';
import { revealOnScroll, useGsap } from '@/composables/useGsap';

defineProps({
    seo: { type: Object, required: true },
    plans: { type: Object, required: true },
    demoEnabled: { type: Boolean, default: false },
});

const root = ref(null);

const audiences = ['Creative agencies', 'Design studios', 'Dev shops', 'Consultancies', 'Freelancers', 'Startups', 'Marketing teams', 'Product studios'];

useGsap(root, ({ motion }) => {
    if (motion) revealOnScroll();
});
</script>

<template>
    <Seo :seo="seo" />
    <div ref="root">
        <HeroSection :demo-enabled="demoEnabled" />

        <section class="border-y border-line bg-surface/40 py-6" aria-label="Who OrbitOps is for">
            <div class="mask-fade-x overflow-hidden">
                <ul class="marquee flex w-max text-small font-medium whitespace-nowrap text-ink-3">
                    <li v-for="(item, index) in [...audiences, ...audiences]" :key="index" class="flex items-center gap-10 pr-10" :aria-hidden="index >= audiences.length || undefined">
                        {{ item }}<span class="size-1 rounded-full bg-line-strong" aria-hidden="true" />
                    </li>
                </ul>
            </div>
        </section>

        <ScrollStory />

        <ProductShowcase />

        <section class="mx-auto max-w-7xl px-5 py-24 sm:px-8 sm:py-32" aria-labelledby="features-heading">
            <SectionHeading
                heading-id="features-heading"
                eyebrow="The platform"
                title="Everything a service business runs on."
                description="Ten connected modules that share one data model — so a tracked hour becomes an invoice line, and an approval becomes a notification, without anyone re-typing anything."
            />
            <div class="mt-14">
                <FeatureBento />
            </div>
        </section>

        <section class="border-y border-line bg-surface/40">
            <div class="mx-auto max-w-7xl px-5 py-24 sm:px-8 sm:py-32">
                <EngineeringSection />
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-5 py-24 sm:px-8 sm:py-32" aria-labelledby="pricing-heading">
            <SectionHeading heading-id="pricing-heading" eyebrow="Pricing" title="Start free. Grow when you do." description="Simple per-seat pricing with every core module included. Upgrade for more projects, portals and reporting." />
            <div class="mt-12">
                <PricingTable :plans="plans" />
            </div>
        </section>

        <CtaSection :demo-enabled="demoEnabled" />
    </div>
</template>

<style scoped>
.marquee {
    animation: marquee 38s linear infinite;
}

@keyframes marquee {
    to {
        transform: translateX(-50%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .marquee {
        animation: none;
    }
}
</style>
