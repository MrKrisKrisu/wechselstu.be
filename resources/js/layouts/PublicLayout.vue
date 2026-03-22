<script setup lang="ts">
defineProps<{
    corporateName?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gpn-white font-sans">
        <header class="border-b-4 border-gpn-orange bg-gpn-black shadow-2xl">
            <div class="mx-auto max-w-4xl px-6 py-4">
                <div class="flex items-center justify-between gap-4">
                    <img
                        src="/logos/Horizontal - Light - nobg.svg"
                        alt="GPN24"
                        class="h-8 w-auto flex-shrink-0"
                    />
                    <div class="flex flex-shrink-0 flex-col items-end">
                        <span
                            class="text-[9px] font-bold tracking-[0.2em] text-gpn-orange uppercase"
                            >Zertifiziert</span
                        >
                        <span class="text-[8px] tracking-wider text-[#8a8280]"
                            >ISO 9001:2025 · DSGVO-konform</span
                        >
                    </div>
                </div>
                <div class="mt-2 border-t border-[#2a2827] pt-2">
                    <div
                        class="font-heading text-[10px] leading-snug font-bold tracking-[0.2em] text-gpn-orange uppercase"
                    >
                        {{
                            corporateName ??
                            'BargeldManagement Solutions GmbH &amp; Co. KG'
                        }}
                    </div>
                    <div
                        class="mt-0.5 text-[9px] tracking-widest text-[#8a8280] uppercase"
                    >
                        Professionelles Bargeldmanagement seit 2026
                    </div>
                </div>
            </div>
        </header>

        <div class="h-0.5 bg-gpn-orange opacity-40"></div>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-[#2a2827] bg-gpn-black py-5">
            <div
                class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-2 px-6 sm:flex-row"
            >
                <div class="flex items-center gap-3">
                    <img
                        src="/logos/Logomark - Light - nobg.svg"
                        alt=""
                        class="h-5 w-auto opacity-50"
                    />
                    <p
                        class="text-[10px] tracking-widest text-[#8a8280] uppercase"
                    >
                        Ihr Partner für professionelles Bargeldmanagement ·
                        GPN24
                    </p>
                </div>
                <p class="text-[9px] tracking-wider text-[#5a5250] uppercase">
                    wechselstu.be · Enterprise Edition
                </p>
            </div>
        </footer>
    </div>
</template>
