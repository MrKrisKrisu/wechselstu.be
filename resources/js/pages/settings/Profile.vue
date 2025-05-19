<script lang="ts" setup>
import {Head, useForm, usePage} from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import {type BreadcrumbItem, type SharedData, type User} from '@/types';

interface Props {
  mustVerifyEmail: boolean;
  status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Profile settings',
    href: '/settings/profile',
  },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
  name: user.name,
  email: user.email,
});

const submit = () => {
  form.patch(route('profile.update'), {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Profile settings"/>

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <HeadingSmall description="Update your name and email address" title="Profile information"/>

        <form class="space-y-6" @submit.prevent="submit">
          <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" autocomplete="name" class="mt-1 block w-full" placeholder="Full name"
                   required/>
            <InputError :message="form.errors.name" class="mt-2"/>
          </div>

          <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input
                id="email"
                v-model="form.email"
                autocomplete="username"
                class="mt-1 block w-full"
                placeholder="Email address"
                required
                type="email"
            />
            <InputError :message="form.errors.email" class="mt-2"/>
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="form.processing">Save</Button>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
              <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
            </Transition>
          </div>
        </form>
      </div>

      <DeleteUser/>
    </SettingsLayout>
  </AppLayout>
</template>
