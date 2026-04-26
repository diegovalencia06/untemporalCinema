<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    user: Object,
    historial: Array,
});

// --- ESTADO ---
const filtroActivo = ref('vistas'); // 'vistas' o 'futuras'
const criterioOrden = ref('fecha'); // 'fecha', 'rating_propio', 'rating_medio'

// --- LÓGICA DE FILTRADO Y ORDENACIÓN ---
const peliculasFuturas = computed(() => props.historial.filter(p => p.es_futuro));

const peliculasVistas = computed(() => {
    let vistas = props.historial.filter(p => !p.es_futuro);
    
    return vistas.sort((a, b) => {
        if (criterioOrden.value === 'fecha') {
            return new Date(b.fecha_sesion_raw) - new Date(a.fecha_sesion_raw);
        } else if (criterioOrden.value === 'rating_propio') {
            return (b.rating || 0) - (a.rating || 0);
        } else if (criterioOrden.value === 'rating_medio') {
            return b.nota_media_global - a.nota_media_global;
        }
        return 0;
    });
});

const puntuarPelicula = (orderId, rating) => {
    router.post(`/pedidos/${orderId}/valorar`, { rating: rating }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Mi Perfil | Untemporal Cinema" />

    <MainLayout>
        <main class="text-[#dce1fb] pt-32 pb-24 px-6 max-w-7xl mx-auto">
            
            <div class="mb-12 flex items-center gap-6 border-b border-white/10 pb-8">
                <div class="w-20 h-20 bg-red-600 rounded-full flex items-center justify-center text-3xl font-black shadow-[0_0_20px_rgba(220,38,38,0.4)]">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <h1 class="font-display text-4xl font-black uppercase tracking-tight">{{ user.name }}</h1>
                    <p class="text-slate-400 font-label text-sm tracking-widest">{{ user.email }}</p>
                </div>
            </div>

            <div class="flex gap-8 mb-10 border-b border-white/5">
                <button @click="filtroActivo = 'vistas'" 
                        :class="['pb-4 text-sm font-black uppercase tracking-widest transition-all', filtroActivo === 'vistas' ? 'text-red-500 border-b-2 border-red-500' : 'text-slate-500 hover:text-slate-300']">
                    Películas Vistas ({{ peliculasVistas.length }})
                </button>
                <button @click="filtroActivo = 'futuras'" 
                        :class="['pb-4 text-sm font-black uppercase tracking-widest transition-all', filtroActivo === 'futuras' ? 'text-red-500 border-b-2 border-red-500' : 'text-slate-500 hover:text-slate-300']">
                    Próximas Sesiones ({{ peliculasFuturas.length }})
                </button>
            </div>

            <div v-if="filtroActivo === 'vistas' && peliculasVistas.length > 0" class="flex items-center gap-4 mb-8">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Ordenar por:</span>
                <select v-model="criterioOrden" class="bg-[#191f31] border-white/10 text-[#dce1fb] text-xs rounded-lg focus:ring-red-500 focus:border-red-500 p-2 uppercase font-bold tracking-wider">
                    <option value="fecha">Fecha (Recientes)</option>
                    <option value="rating_propio">Mi Valoración</option>
                    <option value="rating_medio">Nota de la Comunidad</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <div v-for="reserva in (filtroActivo === 'vistas' ? peliculasVistas : peliculasFuturas)" 
                     :key="reserva.id" 
                     class="bg-[#191f31]/40 border border-white/5 rounded-3xl overflow-hidden flex flex-col group hover:border-red-500/30 transition-all shadow-2xl">
                    
                    <div class="h-40 w-full relative overflow-hidden">
                        <img :src="`https://image.tmdb.org/t/p/w500${reserva.pelicula_poster}`" class="w-full h-full object-cover opacity-40 group-hover:opacity-70 transition-opacity">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#121827] via-transparent to-transparent"></div>
                        
                        <div class="absolute bottom-4 left-5 flex gap-3">
                            <div class="bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10 flex items-center gap-1">
                                <span class="material-symbols-outlined text-yellow-400 text-xs" 
                                    :class="reserva.nota_media_global > 0 ? '[font-variation-settings:\'FILL\'_1]' : '[font-variation-settings:\'FILL\'_0]'">
                                    star
                                </span>
                                <span class="text-[10px] font-bold text-white">
                                    {{ reserva.nota_media_global > 0 ? reserva.nota_media_global : 'N/A' }}
                                </span>
                            </div>
                            
                            <div class="bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10 flex items-center gap-1">
                                <span class="material-symbols-outlined text-red-400 text-xs">group</span>
                                <span class="text-[10px] font-bold text-white">{{ reserva.espectadores_globales }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="text-xl font-black text-white uppercase tracking-tighter mb-1">{{ reserva.pelicula_titulo }}</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-6">{{ reserva.fecha_sesion }} • {{ reserva.hora_sesion }}H</p>

                        <div class="mt-auto pt-6 border-t border-white/5">
                            
                            <div v-if="reserva.es_futuro">
                                <a :href="reserva.url_pdf" target="_blank" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-black uppercase tracking-widest rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-lg shadow-red-600/20">
                                    <span class="material-symbols-outlined text-sm">download</span>
                                    Descargar Entrada
                                </a>
                            </div>

                            <div v-else class="flex flex-col items-center">
                                <span class="text-[9px] uppercase font-black text-slate-600 tracking-[0.3em] mb-3">Tu Valoración</span>
                                <div class="flex gap-1">
                                    <button v-for="i in 5" :key="i" @click="puntuarPelicula(reserva.id, i)" class="hover:scale-125 transition-transform">
                                        <span class="material-symbols-outlined text-2xl transition-colors"
                                              :class="reserva.rating >= i ? 'text-yellow-400 [font-variation-settings:\'FILL\'_1]' : 'text-white/10 hover:text-yellow-400/50'">
                                            star
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="(filtroActivo === 'vistas' ? peliculasVistas : peliculasFuturas).length === 0" class="text-center py-20 bg-white/2 rounded-3xl border border-dashed border-white/10">
                <p class="text-slate-500 uppercase font-black tracking-widest">No hay películas en esta sección</p>
            </div>

        </main>
    </MainLayout>
</template>