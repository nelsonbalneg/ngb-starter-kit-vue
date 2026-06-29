<script setup lang="ts">
import { Search, UserRound, X } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { Spinner } from '@/components/ui/spinner';

type UserOption = {
    id: number;
    name: string;
    email: string;
};

type Props = {
    modelValue: number[];
    searchUrl: string;
    placeholder?: string;
};

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Search by name or email…',
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const query = ref('');
const results = ref<UserOption[]>([]);
const isLoading = ref(false);
const isOpen = ref(false);
const selectedUsers = ref<UserOption[]>([]);
const inputRef = ref<HTMLInputElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

// Fetch existing users by IDs on mount / when modelValue changes externally
const initSelected = async (ids: number[]): Promise<void> => {
    if (!ids.length) {
        selectedUsers.value = [];
        return;
    }

    // Re-use any already-loaded results
    const cached = selectedUsers.value.filter((u) => ids.includes(u.id));
    const missingIds = ids.filter((id) => !cached.some((u) => u.id === id));

    if (!missingIds.length) {
        selectedUsers.value = cached;
        return;
    }

    try {
        const url = new URL(props.searchUrl, window.location.origin);
        url.searchParams.set('ids', missingIds.join(','));
        const res = await fetch(url.toString());
        const data = (await res.json()) as UserOption[];
        const merged = [...cached, ...data];
        selectedUsers.value = merged.filter((u) => ids.includes(u.id));
    } catch {
        // silently keep what we have
    }
};

watch(
    () => props.modelValue,
    (ids) => initSelected(ids),
    { immediate: true },
);

const search = async (q: string): Promise<void> => {
    if (q.length < 2) {
        results.value = [];
        isLoading.value = false;
        return;
    }

    isLoading.value = true;

    try {
        const url = new URL(props.searchUrl, window.location.origin);
        url.searchParams.set('q', q);
        const res = await fetch(url.toString());
        results.value = (await res.json()) as UserOption[];
    } catch {
        results.value = [];
    } finally {
        isLoading.value = false;
    }
};

const onInput = (): void => {
    isOpen.value = true;
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => search(query.value), 350);
};

const filteredResults = computed(() =>
    results.value.filter((r) => !props.modelValue.includes(r.id)),
);

const select = (user: UserOption): void => {
    const updated = [...props.modelValue, user.id];
    selectedUsers.value = [...selectedUsers.value, user];
    emit('update:modelValue', updated);
    query.value = '';
    results.value = [];
    isOpen.value = false;
    nextTick(() => inputRef.value?.focus());
};

const remove = (id: number): void => {
    selectedUsers.value = selectedUsers.value.filter((u) => u.id !== id);
    emit(
        'update:modelValue',
        props.modelValue.filter((uid) => uid !== id),
    );
};

const onBlur = (): void => {
    setTimeout(() => {
        isOpen.value = false;
    }, 150);
};
</script>

<template>
    <div class="relative space-y-1.5">
        <!-- Selected chips -->
        <div v-if="selectedUsers.length" class="flex flex-wrap gap-1">
            <div
                v-for="user in selectedUsers"
                :key="user.id"
                class="flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-[12px]"
            >
                <UserRound class="size-3 text-muted-foreground" />
                <span class="font-medium">{{ user.name }}</span>
                <span class="text-muted-foreground">· {{ user.email }}</span>
                <button
                    type="button"
                    :aria-label="`Remove ${user.name}`"
                    class="ml-0.5 rounded-sm text-muted-foreground hover:text-destructive focus:outline-none"
                    @click="remove(user.id)"
                >
                    <X class="size-3" />
                </button>
            </div>
        </div>

        <!-- Search input -->
        <div class="relative">
            <Search class="pointer-events-none absolute left-2 top-2 size-4 text-muted-foreground" />
            <input
                ref="inputRef"
                v-model="query"
                type="text"
                :placeholder="placeholder"
                class="h-8 w-full rounded-md border bg-background pl-8 pr-8 text-[13px] outline-none ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
                autocomplete="off"
                @input="onInput"
                @focus="isOpen = query.length >= 2"
                @blur="onBlur"
                @keydown.escape="isOpen = false"
            />
            <Spinner v-if="isLoading" class="absolute right-2 top-2 size-4 text-muted-foreground" />
        </div>

        <!-- Dropdown results -->
        <div
            v-if="isOpen"
            class="absolute left-0 z-50 mt-1 w-full rounded-md border bg-popover shadow-md"
            style="top: 100%"
        >
            <template v-if="query.length < 2">
                <p class="px-3 py-2 text-[12px] text-muted-foreground">
                    Type at least 2 characters to search.
                </p>
            </template>
            <template v-else-if="isLoading">
                <div class="flex items-center gap-2 px-3 py-2 text-[12px] text-muted-foreground">
                    <Spinner class="size-3" />
                    Searching…
                </div>
            </template>
            <template v-else-if="filteredResults.length">
                <ul role="listbox" class="max-h-52 overflow-y-auto py-1">
                    <li
                        v-for="user in filteredResults"
                        :key="user.id"
                        role="option"
                        class="flex cursor-pointer flex-col px-3 py-1.5 hover:bg-accent"
                        @mousedown.prevent="select(user)"
                    >
                        <span class="text-[13px] font-medium leading-tight">{{ user.name }}</span>
                        <span class="text-[11px] text-muted-foreground">{{ user.email }}</span>
                    </li>
                </ul>
            </template>
            <template v-else>
                <p class="px-3 py-2 text-[12px] text-muted-foreground">No users found for "{{ query }}".</p>
            </template>
        </div>

        <!-- Helper text -->
        <p class="text-[11px] text-muted-foreground">
            Search and select one or more head-of-office users.
        </p>
    </div>
</template>
