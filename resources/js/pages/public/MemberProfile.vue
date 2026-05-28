<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import CheckIcon from 'vue-material-design-icons/Check.vue';
import StarIcon from 'vue-material-design-icons/Star.vue';
import StarOutlineIcon from 'vue-material-design-icons/StarOutline.vue';
import { useRoute } from 'vue-router';
import PublicLayout from '@/layouts/PublicLayout.vue';
import axios from '@/lib/axios';

const route = useRoute();
const token = route.params.token as string;

const member = ref<{
    name: string;
    bio: string | null;
    appearance: string | null;
    avatar_url: string | null;
} | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const REVIEWS = [
    {
        text: 'Handed over the cash with no problems. Everything was correct.',
        author: 'K. Möller',
        rating: 5,
    },
    {
        text: 'Card reader stopped working. They fixed it fast. No long wait for customers.',
        author: 'T. Braun',
        rating: 5,
    },
    {
        text: 'Brought change for all three tills at once. Very helpful.',
        author: 'L. Schmidt',
        rating: 5,
    },
    {
        text: 'The coins were already sorted. Made my job much easier.',
        author: 'F. Wagner',
        rating: 4,
    },
    {
        text: 'Someone tried to pay with a fake note. They caught it right away.',
        author: 'M. Richter',
        rating: 5,
    },
    {
        text: 'Payment terminal had an issue. They dealt with it quickly and stayed calm.',
        author: 'A. Fischer',
        rating: 5,
    },
    {
        text: 'Topped up the cash box before I even asked. Good thinking.',
        author: 'P. Krause',
        rating: 5,
    },
    {
        text: 'Counted everything at the end of the day. Not a single cent missing.',
        author: 'S. Wolf',
        rating: 5,
    },
    {
        text: 'Card payments went down. They had cash ready as a backup. No stress.',
        author: 'J. Koch',
        rating: 4,
    },
    {
        text: 'Brought a bag of change during a busy time without being asked. Great.',
        author: 'R. Bauer',
        rating: 5,
    },
    {
        text: 'Counted the notes twice and explained every step. Very professional.',
        author: 'H. Neumann',
        rating: 5,
    },
    {
        text: 'Picked up a large cash amount without any issues. Brought a receipt too.',
        author: 'E. Weber',
        rating: 4,
    },
];

function pickReviews(seed: string, count = 3) {
    const offset = seed.split('').reduce((a, c) => a + c.charCodeAt(0), 0);
    const shuffled = [...REVIEWS]
        .map((r, i) => ({
            r,
            sort:
                (i + offset + Math.random() * REVIEWS.length) % REVIEWS.length,
        }))
        .sort((a, b) => a.sort - b.sort)
        .map((x) => x.r);

    return shuffled.slice(0, count);
}

const reviews = ref<typeof REVIEWS>([]);

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/members/${token}`);
        member.value = data.member;
        reviews.value = pickReviews(token);
    } catch {
        error.value = 'Profile not found.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <PublicLayout
        corporate-name="Personal Verification Unit™ / Staff Identification"
    >
        <div class="mx-auto max-w-4xl px-6 py-12">
            <div v-if="loading" class="flex items-center justify-center py-24">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-4 border-gpn-orange border-t-transparent"
                />
            </div>

            <div v-else-if="error" class="py-24 text-center">
                <p class="text-lg text-red-600">{{ error }}</p>
            </div>

            <div v-else-if="member" class="space-y-8">
                <div
                    class="flex items-center gap-6 border-b-2 border-gpn-orange pb-8"
                >
                    <div
                        class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-gpn-black shadow-md"
                    >
                        <img
                            v-if="member.avatar_url"
                            :alt="member.name"
                            :src="member.avatar_url"
                            class="h-full w-full object-cover"
                        />
                        <span v-else class="text-2xl font-bold text-gpn-orange">
                            {{ member.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <p
                            class="mb-1 text-sm font-bold tracking-[0.2em] text-gpn-orange uppercase"
                        >
                            Staff Verification
                        </p>
                        <h1
                            class="font-heading text-3xl leading-tight font-bold text-gpn-black sm:text-4xl"
                        >
                            You are looking at {{ member.name }}.
                        </h1>
                    </div>
                </div>

                <div
                    v-if="member.appearance"
                    class="rounded-lg border border-gpn-orange/40 bg-gpn-orange/5 p-6 shadow-sm"
                >
                    <p
                        class="mb-2 text-xs font-semibold tracking-widest text-gpn-orange uppercase"
                    >
                        How to recognize this person
                    </p>
                    <p class="text-base text-gray-800">
                        {{ member.appearance }}
                    </p>
                </div>

                <div
                    v-if="member.bio"
                    class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <p
                        class="mb-2 text-xs font-semibold tracking-widest text-gpn-orange uppercase"
                    >
                        About
                    </p>
                    <p
                        class="text-base leading-relaxed whitespace-pre-wrap text-gray-700"
                    >
                        {{ member.bio }}
                    </p>
                </div>

                <div
                    class="flex items-center gap-3 rounded-lg border border-gpn-orange/30 bg-gpn-orange/5 px-5 py-4"
                >
                    <CheckIcon
                        :size="20"
                        class="flex-shrink-0 text-gpn-orange"
                    />
                    <p class="text-sm text-gray-600">
                        This person is a verified member of the GELD team and
                        authorized to handle cash operations.
                    </p>
                </div>

                <div>
                    <p
                        class="mb-4 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                    >
                        Customer Reviews
                    </p>
                    <div class="space-y-3">
                        <div
                            v-for="(review, i) in reviews"
                            :key="i"
                            class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm"
                        >
                            <div class="mb-2 flex items-center gap-0.5">
                                <component
                                    v-for="s in 5"
                                    :key="s"
                                    :is="
                                        s <= review.rating
                                            ? StarIcon
                                            : StarOutlineIcon
                                    "
                                    :size="16"
                                    :class="
                                        s <= review.rating
                                            ? 'text-gpn-orange'
                                            : 'text-gray-300'
                                    "
                                />
                            </div>
                            <p class="text-sm text-gray-700">
                                {{ review.text }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                - {{ review.author }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
