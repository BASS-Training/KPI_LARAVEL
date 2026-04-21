<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Alert from '@/components/ui/Alert.vue';
import Input from '@/components/ui/Input.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import LoadingRows from '@/components/shared/LoadingRows.vue';
import { useSettingStore } from '@/stores/setting';
import { useToast } from '@/composables/useToast';

const store = useSettingStore();
const toast = useToast();
const saving = ref(false);
const errorMessage = ref('');
const form = reactive({});

const settingList = computed(() => store.settings);

onMounted(async () => {
    await store.fetchSettings();
    syncForm();
});

function syncForm() {
    Object.keys(form).forEach((key) => delete form[key]);
    settingList.value.forEach((item) => {
        form[item.key] = item.value ?? '';
    });
}

async function submit() {
    saving.value = true;
    errorMessage.value = '';

    try {
        await store.updateSettings({ ...form });
        toast.success('Pengaturan berhasil diperbarui.');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Gagal memperbarui pengaturan.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <PageHeader
            eyebrow="HR Panel"
            title="Pengaturan Aplikasi"
            description="Perbarui parameter umum aplikasi KPI tanpa mengubah file konfigurasi manual."
        />

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">HR Panel</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Parameter Sistem</h2>
                <p class="mt-1 text-sm text-slate-500">Semua perubahan disimpan sebagai setting aplikasi.</p>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <LoadingRows :rows="5" />
                </template>

                <template v-else-if="settingList.length">
                    <Alert v-if="errorMessage" variant="danger" class="mb-4">{{ errorMessage }}</Alert>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="item in settingList" :key="item.key">
                            <label class="form-label">{{ item.key }}</label>
                            <Input v-model="form[item.key]" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button class="btn-primary" :disabled="saving" @click="submit">
                            {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                        </button>
                    </div>
                </template>

                <EmptyState
                    v-else
                    title="Belum ada data pengaturan"
                    description="Data setting belum tersedia dari API."
                />
            </div>
        </section>
    </AppLayout>
</template>
