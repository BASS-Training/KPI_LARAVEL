<script setup>
import { onMounted, computed } from 'vue';
import { useTaskStore } from '@/stores/task';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import Table from '@/components/ui/Table.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableRow from '@/components/ui/TableRow.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableCell from '@/components/ui/TableCell.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const taskStore = useTaskStore();
const tasks = computed(() => taskStore.tasks);
const toast = useToast();

onMounted(() => taskStore.fetchTasks());

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusVariantMap = { 'Selesai': 'success', 'Dalam Proses': 'default', 'Pending': 'warning' };
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR Monitoring</div>
                    <h2 class="mt-4 text-2xl font-bold md:text-3xl">Mapping pekerjaan ke komponen KPI</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Pastikan setiap pekerjaan pegawai terhubung ke objective KPI yang tepat agar penilaian bulanan terbaca akurat.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:min-w-[320px]">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Total Task</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ tasks.length }}</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Belum Di-map</div>
                        <div class="mt-2 text-2xl font-bold text-white">
                            {{ tasks.filter((task) => !task.kpi_component).length }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Semua pekerjaan pegawai</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <template v-if="taskStore.isLoading">
                    <div class="space-y-2 p-5">
                        <Skeleton v-for="i in 8" :key="i" class="h-12" />
                    </div>
                </template>
                <template v-else-if="tasks.length">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Pegawai</TableHead>
                                <TableHead>Judul</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>KPI</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="task in tasks" :key="task.id">
                                <TableCell>{{ formatDate(task.tanggal) }}</TableCell>
                                <TableCell>{{ task.user?.nama || '-' }}</TableCell>
                                <TableCell class="max-w-[200px] truncate">{{ task.judul }}</TableCell>
                                <TableCell>
                                    <Badge :variant="statusVariantMap[task.status] || 'outline'">
                                        {{ task.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="task.kpi_component" variant="default">
                                        {{ task.kpi_component?.objectives }}
                                    </Badge>
                                    <span v-else class="text-xs text-slate-400">Belum di-map</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </template>
                <div v-else class="py-12 text-center text-sm text-slate-400">
                    Belum ada data pekerjaan.
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
