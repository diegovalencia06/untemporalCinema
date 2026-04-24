<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    order: Object,
    downloadUrl: String
});
</script>

<template>
    <Head title="¡Compra Exitosa!" />

    <MainLayout>
        <main class="min-h-screen text-[#dce1fb] flex items-center justify-center pt-32 pb-24 px-6">
            
            <div class="bg-[#191f31]/70 backdrop-blur-[20px] border border-white/10 rounded-3xl p-10 max-w-lg w-full text-center shadow-2xl">
                
                <div class="w-20 h-20 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_30px_rgba(34,197,94,0.3)]">
                    <span class="material-symbols-outlined text-4xl font-bold">check</span>
                </div>

                <h1 class="font-display text-3xl font-black tracking-tighter mb-2 uppercase">¡Compra Realizada!</h1>
                <p class="text-slate-400 text-sm mb-8">
                    Tu reserva para <strong class="text-white">{{ order.session.movie.title }}</strong> se ha procesado correctamente.
                </p>

                <div class="bg-[#0c1324] border border-white/5 rounded-2xl p-6 mb-8 text-left">
                    <p class="text-xs uppercase tracking-widest text-slate-500 mb-1">Referencia del Pedido</p>
                    <p class="text-xl font-mono font-bold text-white mb-4">{{ order.reference }}</p>
                    
                    <div class="flex justify-between items-end border-t border-white/5 pt-4">
                        <span class="text-sm text-slate-400">Total Pagado:</span>
                        <span class="text-lg font-black text-green-400">{{ order.total_price }}€</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <a 
                        :href="downloadUrl"
                        target="_blank"
                        download 
                        class="w-full flex items-center justify-center gap-2 py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black uppercase tracking-widest transition-all active:scale-95 shadow-lg shadow-red-600/20"
                    >
                        <span class="material-symbols-outlined">download</span>
                        Descargar Entradas (PDF)
                    </a>

                    <Link 
                        href="/" 
                        class="w-full flex items-center justify-center py-4 bg-white/5 hover:bg-white/10 text-white rounded-2xl font-bold uppercase tracking-widest transition-all"
                    >
                        Volver a la Cartelera
                    </Link>
                </div>

            </div>

        </main>
    </MainLayout>
</template>