<script lang="ts" setup>
import UserInfo from '@/components/UserInfo.vue';
import {DropdownMenu, DropdownMenuContent, DropdownMenuTrigger} from '@/components/ui/dropdown-menu';
import {SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar} from '@/components/ui/sidebar';
import {type SharedData, type User} from '@/types';
import {usePage} from '@inertiajs/vue3';
import {ChevronsUpDown} from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const {isMobile, state} = useSidebar();
</script>

<template>
  <SidebarMenu>
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <SidebarMenuButton class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                             size="lg">
            <UserInfo :user="user"/>
            <ChevronsUpDown class="ml-auto size-4"/>
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
            :side-offset="4"
            align="end"
            class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
        >
          <UserMenuContent :user="user"/>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>
</template>
