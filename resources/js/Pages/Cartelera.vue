<script setup>
import { ref, computed } from 'vue';
// 1. IMPORTAMOS 'Head' AQUÍ
import { Head, Link, usePage } from '@inertiajs/vue3'; 
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    movies: Array,
    semana: Array
});

const fechaSeleccionada = ref(props.semana[0].fecha_completa);

const peliculasFiltradas = computed(() => {
    const ahora = new Date(); // Obtenemos la hora exacta actual

    return props.movies.map(movie => {
        const sesionesDelDia = movie.sessions.filter(session => {
            const inicioSesion = new Date(session.start_time);
            const fechaSesion = session.start_time.split(' ')[0].split('T')[0]; 
            
            // CONDICIÓN: Que sea el día seleccionado Y que la hora no haya pasado
            return fechaSesion === fechaSeleccionada.value && inicioSesion > ahora;
        });
        return { ...movie, sesionesDelDia };
    }).filter(movie => movie.sesionesDelDia.length > 0);
});

const formatearHora = (fechaString) => {
    const fecha = new Date(fechaString);
    return fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Cartelera" />

    <MainLayout>
        <div class="pt-24 pb-24 max-w-screen-2xl mx-auto px-6">
            
            <section class="mb-12">
                <h2 class="font-headline text-3xl font-extrabold tracking-tight mb-6 text-on-surface md:text-center">Elige tu día</h2>
                
                <div class="flex justify-start md:justify-center overflow-x-auto no-scrollbar gap-5 pb-8 pt-4 px-2 w-full">
                    <button 
                        v-for="dia in semana" 
                        :key="dia.fecha_completa"
                        @click="fechaSeleccionada = dia.fecha_completa"
                        :class="[
                            'flex-shrink-0 flex flex-col items-center justify-center w-20 h-28 rounded-xl transition-all duration-300 relative', 
                            fechaSeleccionada === dia.fecha_completa 
                                ? 'bg-primary-container text-on-primary-container shadow-[0_10px_30px_rgba(220,38,38,0.4)] scale-110 z-10 ring-2 ring-primary/30' 
                                : 'bg-surface-container-low text-on-surface-variant border border-white/5 hover:bg-surface-container z-0 hover:z-10'
                        ]"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-1">{{ dia.label }}</span>
                        <span class="text-3xl font-black font-headline">{{ dia.numero }}</span>
                    </button>
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-headline text-3xl font-extrabold tracking-tight text-on-surface">En Cartelera</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-x-6 gap-y-12">
                    
                    <div v-for="movie in peliculasFiltradas" :key="movie.id" class="group flex flex-col h-full bg-surface-container-low/30 border border-white/5 rounded-2xl p-4 hover:bg-surface-container-low transition-colors duration-300">
    
                        <Link :href="`/pelicula/${movie.id}`" class="flex flex-col cursor-pointer">
                            <div class="aspect-[2/3] relative rounded-xl overflow-hidden bg-surface-container-highest shadow-lg shrink-0">
                                <img v-if="movie.poster_path" :src="`https://image.tmdb.org/t/p/w500${movie.poster_path}`" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                <div v-else class="flex items-center justify-center h-full text-surface-variant italic">Sin póster</div>
                                <div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest/90 via-transparent to-transparent opacity-90"></div>
                            </div>
                            
                            <h3 class="font-headline font-bold text-on-surface leading-tight group-hover:text-primary transition-colors mt-4 mb-4">
                                {{ movie.title }}
                            </h3>
                        </Link>
                        
                        <div class="flex flex-wrap gap-2">
                            <Link v-for="session in movie.sesionesDelDia" :key="session.id" 
                            :href="`/sesion/${session.id}/asientos`"
                            class="px-4 py-2 bg-surface-container text-xs text-white font-bold rounded-lg border border-white/10 hover:border-primary hover:bg-primary/10 transition-all active:scale-95 text-center">
                                {{ formatearHora(session.start_time) }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="peliculasFiltradas.length === 0" class="col-span-full py-16 text-center text-on-surface-variant bg-surface-container-low rounded-2xl border border-white/5">
                        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">videocam_off</span>
                        <p class="font-headline font-bold">No hay sesiones para este día</p>
                    </div>

                </div>
            </section>
        </div>

        <nav class="md:hidden fixed bottom-0 left-0 w-full flex justify-around items-center px-4 py-3 pb-safe bg-[#0c1324]/90 backdrop-blur-2xl border-t border-white/5 shadow-[0_-10px_40px_rgba(220,38,38,0.15)] z-50 rounded-t-2xl">
            <button class="flex flex-col items-center justify-center text-red-500 bg-red-500/10 rounded-xl px-4 py-2 scale-110 transition-transform">
                <span class="material-symbols-outlined font-variation-settings: 'FILL' 1;">movie</span>
                <span class="font-headline text-[10px] font-bold uppercase tracking-widest mt-1">Pelis</span>
            </button>
            <button class="flex flex-col items-center justify-center text-slate-500 hover:text-slate-300 active:scale-90 transition-all duration-200">
                <span class="material-symbols-outlined">confirmation_number</span>
                <span class="font-headline text-[10px] font-bold uppercase tracking-widest mt-1">Tickets</span>
            </button>
            <button class="flex flex-col items-center justify-center text-slate-500 hover:text-slate-300 active:scale-90 transition-all duration-200">
                <span class="material-symbols-outlined">person</span>
                <span class="font-headline text-[10px] font-bold uppercase tracking-widest mt-1">Perfil</span>
            </button>
        </nav>
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>