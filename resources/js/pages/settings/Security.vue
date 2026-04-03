<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Keamanan',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Keamanan" />

    <h1 class="sr-only">Keamanan</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Ubah Password"
            description="Gunakan password yang kuat dan unik untuk keamanan akun Anda"
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{ preserveScroll: true }"
            reset-on-success
            :reset-on-error="['password', 'password_confirmation', 'current_password']"
            class="space-y-6"
            v-slot="{ errors, processing, recentlySuccessful }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Password Saat Ini</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Password saat ini"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password Baru</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Password baru"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi Password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Konfirmasi password baru"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-password-button">
                    Simpan Password
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
                        Password berhasil diperbarui.
                    </p>
                </Transition>
            </div>
        </Form>
    </div>
</template>
