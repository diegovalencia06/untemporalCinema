<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3'; 
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    movies: Array,
    semana: Array
});

const fechaSeleccionada = ref(props.semana[0].fecha_completa);

const peliculasFiltradas = computed(() => {
    const ahora = new Date(); 
    return props.movies.map(movie => {
        const sesionesDelDia = movie.sessions.filter(session => {
            const inicioSesion = new Date(session.start_time);
            const fechaSesion = session.start_time.split(' ')[0].split('T')[0]; 
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
        <div class="pt-20 md:pt-24 pb-24 max-w-screen-2xl mx-auto px-4 md:px-6">
            
            <section class="mb-8 md:mb-12">
                <h2 class="font-headline text-2xl md:text-3xl font-extrabold tracking-tight mb-6 text-on-surface text-center">Elige tu día</h2>
                <div class="flex justify-start md:justify-center overflow-x-auto no-scrollbar gap-3 md:gap-5 pb-6 pt-2 px-2 w-full">
                    <button 
                        v-for="dia in semana" 
                        :key="dia.fecha_completa"
                        @click="fechaSeleccionada = dia.fecha_completa"
                        :class="[
                            'flex-shrink-0 flex flex-col items-center justify-center w-16 h-24 md:w-20 md:h-28 rounded-xl transition-all duration-300 relative', 
                            fechaSeleccionada === dia.fecha_completa 
                                ? 'bg-primary-container text-on-primary-container shadow-xl scale-110 z-10 ring-2 ring-primary/30' 
                                : 'bg-surface-container-low text-on-surface-variant border border-white/5'
                        ]"
                    >
                        <span class="text-[8px] md:text-[10px] font-bold uppercase tracking-widest opacity-80 mb-1">{{ dia.label }}</span>
                        <span class="text-2xl md:text-3xl font-black font-headline">{{ dia.numero }}</span>
                    </button>
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-headline text-2xl md:text-3xl font-extrabold tracking-tight text-on-surface">En Cartelera</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-x-4 md:gap-x-6 gap-y-8 md:gap-y-12">
                    <div v-for="movie in peliculasFiltradas" :key="movie.id" class="group flex flex-col h-full bg-surface-container-low/30 border border-white/5 rounded-2xl p-3 md:p-4 hover:bg-surface-container-low transition-colors duration-300">
                        <Link :href="`/pelicula/${movie.id}`" class="flex flex-col cursor-pointer mb-4">
                            <div class="aspect-[2/3] relative rounded-xl overflow-hidden bg-surface-container-highest shadow-lg shrink-0">
                                <img v-if="movie.poster_path" :src="`https://image.tmdb.org/t/p/w500${movie.poster_path}`" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                <div v-else class="flex items-center justify-center h-full text-surface-variant italic text-xs">Sin póster</div>
                                <div class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest/90 via-transparent to-transparent opacity-90"></div>
                            </div>
                            <h3 class="font-headline font-bold text-sm md:text-base text-on-surface leading-tight group-hover:text-primary transition-colors mt-3 line-clamp-2">
                                {{ movie.title }}
                            </h3>
                        </Link>
                        <div class="flex flex-wrap gap-2 mt-auto">
                            <Link v-for="session in movie.sesionesDelDia" :key="session.id" 
                            :href="`/sesion/${session.id}/asientos`"
                            class="flex-1 min-w-[60px] px-2 py-2 bg-surface-container text-[10px] md:text-xs text-white font-bold rounded-lg border border-white/10 hover:border-primary transition-all text-center">
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
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>