<script lang="ts" setup>
import UserInfo from '@/components/UserInfo.vue';
import {
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import type {User} from '@/types';
import {Link, router} from '@inertiajs/vue3';
import {LogOut, Settings} from 'lucide-vue-next';

interface Props {
  user: User;
}

const handleLogout = () => {
  router.flushAll();
};

defineProps<Props>();
</script>

<template>
  <DropdownMenuLabel class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
      <UserInfo :show-email="true" :user="user"/>
    </div>
  </DropdownMenuLabel>
  <DropdownMenuSeparator/>
  <DropdownMenuGroup>
    <DropdownMenuItem :as-child="true">
      <Link :href="route('profile.edit')" as="button" class="block w-full" prefetch>
        <Settings class="mr-2 h-4 w-4"/>
        Settings
      </Link>
    </DropdownMenuItem>
  </DropdownMenuGroup>
  <DropdownMenuSeparator/>
  <DropdownMenuItem :as-child="true">
    <Link :href="route('logout')" as="button" class="block w-full" method="post" @click="handleLogout">
      <LogOut class="mr-2 h-4 w-4"/>
      Log out
    </Link>
  </DropdownMenuItem>
</template>
