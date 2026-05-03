<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    user: Object,
    historial: Array,
});

// --- ESTADO DE NAVEGACIÓN ---
const tabActiva = ref('entradas'); // entradas, estadisticas, ajustes
const filtroEntradas = ref('vistas'); // vistas, futuras (sub-filtro de entradas)
const criterioOrden = ref('fecha');

// --- LÓGICA DE FILTRADO (ENTRADAS) ---
const peliculasFuturas = computed(() => props.historial.filter(p => p.es_futuro));
const peliculasVistas = computed(() => {
    let vistas = props.historial.filter(p => !p.es_futuro);
    return vistas.sort((a, b) => {
        if (criterioOrden.value === 'fecha') return new Date(b.fecha_sesion_raw) - new Date(a.fecha_sesion_raw);
        if (criterioOrden.value === 'rating_propio') return (b.rating || 0) - (a.rating || 0);
        if (criterioOrden.value === 'rating_medio') return b.nota_media_global - a.nota_media_global;
        return 0;
    });
});

// --- LÓGICA DE ESTADÍSTICAS ---
const stats = computed(() => {
    const vistas = props.historial.filter(p => !p.es_futuro);
    const totalVistas = vistas.length;
    const promedioRating = vistas.reduce((acc, p) => acc + (p.rating || 0), 0) / (vistas.filter(p => p.rating).length || 1);
    
    return {
        totalVistas,
        promedioRating: promedioRating.toFixed(1),
        // Podrías añadir más lógica si el prop historial trae géneros o duraciones
    };
});

// --- LÓGICA DE AJUSTES (FORMULARIO) ---
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put('/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

const puntuarPelicula = (orderId, rating) => {
    router.post(`/pedidos/${orderId}/valorar`, { rating: rating }, { preserveScroll: true });
};

// --- LÓGICA DE GÉNEROS ---
const statsGeneros = computed(() => {
    const vistas = props.historial.filter(p => !p.es_futuro);
    const conteo = {};

    vistas.forEach(reserva => {
        // Soporta tanto array como string de géneros
        const generosPeli = Array.isArray(reserva.generos) 
            ? reserva.generos 
            : (reserva.generos ? reserva.generos.split(',') : []);

        generosPeli.forEach(g => {
            const nombre = g.trim();
            conteo[nombre] = (conteo[nombre] || 0) + 1;
        });
    });

    return Object.entries(conteo)
        .map(([nombre, cuenta]) => ({
            nombre,
            cuenta,
            porcentaje: Math.round((cuenta / vistas.length) * 100)
        }))
        .sort((a, b) => b.cuenta - a.cuenta) // Ordenar de más visto a menos
        .slice(0, 5); // Mostrar los 5 principales
});
</script>

<template>
    <Head title="Mi Perfil | Untemporal Cinema" />

    <MainLayout>
        <main class="text-[#dce1fb] pt-32 pb-24 px-6 max-w-7xl mx-auto">
            
            <!-- HEADER DE PERFIL -->
            <div class="mb-12 flex items-center gap-6 border-b border-white/10 pb-8">
                <div class="w-20 h-20 bg-red-600 rounded-full flex items-center justify-center text-3xl font-black shadow-[0_0_20px_rgba(220,38,38,0.4)]">
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <h1 class="font-display text-4xl font-black uppercase tracking-tight">{{ user.name }}</h1>
                    <p class="text-slate-400 font-label text-sm tracking-widest">{{ user.email }}</p>
                </div>
            </div>

            <!-- NAVEGACIÓN PRINCIPAL (TABS) -->
            <div class="flex gap-8 mb-10 border-b border-white/5">
                <button @click="tabActiva = 'entradas'" 
                        :class="['pb-4 text-sm font-black uppercase tracking-widest transition-all flex items-center gap-2', tabActiva === 'entradas' ? 'text-red-500 border-b-2 border-red-500' : 'text-slate-500 hover:text-slate-300']">
                    <span class="material-symbols-outlined text-sm">confirmation_number</span> Entradas
                </button>
                <button @click="tabActiva = 'estadisticas'" 
                        :class="['pb-4 text-sm font-black uppercase tracking-widest transition-all flex items-center gap-2', tabActiva === 'estadisticas' ? 'text-red-500 border-b-2 border-red-500' : 'text-slate-500 hover:text-slate-300']">
                    <span class="material-symbols-outlined text-sm">monitoring</span> Estadísticas
                </button>
                <button @click="tabActiva = 'ajustes'" 
                        :class="['pb-4 text-sm font-black uppercase tracking-widest transition-all flex items-center gap-2', tabActiva === 'ajustes' ? 'text-red-500 border-b-2 border-red-500' : 'text-slate-500 hover:text-slate-300']">
                    <span class="material-symbols-outlined text-sm">settings</span> Ajustes
                </button>
            </div>

            <!-- CONTENIDO: ENTRADAS -->
            <div v-if="tabActiva === 'entradas'">
                <div class="flex gap-6 mb-8">
                    <button @click="filtroEntradas = 'vistas'" :class="['text-[10px] font-black uppercase tracking-[0.2em]', filtroEntradas === 'vistas' ? 'text-white' : 'text-slate-600']">Vistas ({{ peliculasVistas.length }})</button>
                    <button @click="filtroEntradas = 'futuras'" :class="['text-[10px] font-black uppercase tracking-[0.2em]', filtroEntradas === 'futuras' ? 'text-white' : 'text-slate-600']">Próximas ({{ peliculasFuturas.length }})</button>
                </div>

                <!-- SELECT DE ORDENACIÓN -->
                <div v-if="filtroEntradas === 'vistas' && peliculasVistas.length > 0" class="flex items-center gap-4 mb-8">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Ordenar por:</span>
                    <select v-model="criterioOrden" class="bg-[#191f31] border-white/10 text-[#dce1fb] text-xs rounded-lg p-2 uppercase font-bold tracking-wider">
                        <option value="fecha">Fecha</option>
                        <option value="rating_propio">Mi Valoración</option>
                        <option value="rating_medio">Comunidad</option>
                    </select>
                </div>

                <!-- GRID DE PELÍCULAS (Tu código original adaptado) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <div v-for="reserva in (filtroEntradas === 'vistas' ? peliculasVistas : peliculasFuturas)" 
         :key="reserva.id" 
         class="bg-[#191f31]/40 border border-white/5 rounded-3xl overflow-hidden flex flex-col group hover:border-red-500/30 transition-all shadow-2xl relative">
        
        <!-- Contenedor de Imagen (Poster) -->
        <div class="h-40 w-full relative overflow-hidden">
            <img :src="`https://image.tmdb.org/t/p/w500${reserva.pelicula_poster}`" 
                 class="w-full h-full object-cover opacity-40 group-hover:opacity-70 transition-opacity duration-500">
            
            <!-- Gradiente para que el texto resalte -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#121827] via-transparent to-transparent"></div>

            <!-- STATS OVERLAY (Ahora dentro del relative) -->
            <div class="absolute bottom-4 left-5 flex gap-3 z-10">
                <!-- Valoración Media -->
                <div class="bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10 flex items-center gap-1">
                    <span class="material-symbols-outlined text-yellow-400 text-xs" 
                        :class="reserva.nota_media_global > 0 ? '[font-variation-settings:\'FILL\'_1]' : '[font-variation-settings:\'FILL\'_0]'">
                        star
                    </span>
                    <span class="text-[10px] font-bold text-white">
                        {{ reserva.nota_media_global > 0 ? reserva.nota_media_global : 'N/A' }}
                    </span>
                </div>
                
                <!-- Espectadores -->
                <div class="bg-black/60 backdrop-blur-md px-2 py-1 rounded-md border border-white/10 flex items-center gap-1">
                    <span class="material-symbols-outlined text-red-400 text-xs">group</span>
                    <span class="text-[10px] font-bold text-white">
                        {{ reserva.espectadores_globales || 0 }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Contenido de la Tarjeta -->
        <div class="p-6 flex-1 flex flex-col">
            <h3 class="text-xl font-black text-white uppercase tracking-tighter mb-1 truncate">
                {{ reserva.pelicula_titulo }}
            </h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase mb-6 tracking-widest">
                {{ reserva.fecha_sesion }}
            </p>

            <div class="mt-auto pt-6 border-t border-white/5">
                <!-- Botón: Próximas Sesiones -->
                <a v-if="reserva.es_futuro" 
                   :href="reserva.url_pdf" 
                   target="_blank" 
                   class="w-full py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-black uppercase rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-sm">download</span> 
                    Descargar Ticket
                </a>

                <!-- Valoración: Películas Vistas -->
                <div v-else class="flex flex-col items-center">
                    <span class="text-[9px] uppercase font-black text-slate-600 tracking-[0.3em] mb-3">Tu Valoración</span>
                    <div class="flex gap-1">
                        <button v-for="i in 5" 
                                :key="i" 
                                @click="puntuarPelicula(reserva.id, i)" 
                                class="hover:scale-125 transition-transform">
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
            </div>

            <!-- CONTENIDO: ESTADÍSTICAS -->
<div v-if="tabActiva === 'estadisticas'" class="space-y-12">
    
    <!-- Tarjetas de Resumen Rápido -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-[#191f31] p-8 rounded-3xl border border-white/5 flex items-center gap-6">
            <div class="w-16 h-16 bg-red-600/10 rounded-2xl flex items-center justify-center text-red-500">
                <span class="material-symbols-outlined text-3xl">movie</span>
            </div>
            <div>
                <p class="text-slate-500 text-[10px] uppercase font-black tracking-widest">Total Vistas</p>
                <p class="text-3xl font-black text-white">{{ stats.totalVistas }}</p>
            </div>
        </div>

        <div class="bg-[#191f31] p-8 rounded-3xl border border-white/5 flex items-center gap-6">
            <div class="w-16 h-16 bg-yellow-600/10 rounded-2xl flex items-center justify-center text-yellow-500">
                <span class="material-symbols-outlined text-3xl">star</span>
            </div>
            <div>
                <p class="text-slate-500 text-[10px] uppercase font-black tracking-widest">Valoración Media</p>
                <p class="text-3xl font-black text-white">{{ stats.promedioRating }}</p>
            </div>
        </div>
    </div>

    <!-- Tabla de Géneros Favoritos -->
    <div class="bg-[#191f31] rounded-3xl border border-white/5 overflow-hidden">
        <div class="p-8 border-b border-white/5 flex justify-between items-center">
            <h3 class="text-xl font-black text-white uppercase tracking-tighter">Tu ADN Cinematográfico</h3>
            <span class="text-[10px] font-black text-red-500 uppercase tracking-[0.2em]">Top 5 Géneros</span>
        </div>
        
        <div class="p-8">
            <div v-if="statsGeneros.length > 0" class="space-y-6">
                <div v-for="genero in statsGeneros" :key="genero.nombre" class="space-y-2">
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-bold text-white uppercase tracking-wider">{{ genero.nombre }}</span>
                        <span class="text-[10px] font-black text-slate-500">{{ genero.cuenta }} {{ genero.cuenta === 1 ? 'película' : 'películas' }}</span>
                    </div>
                    <!-- Barra de progreso con estilo de la app -->
                    <div class="w-full h-2 bg-[#0c1324] rounded-full overflow-hidden">
                        <div class="h-full bg-red-600 rounded-full shadow-[0_0_10px_rgba(220,38,38,0.5)] transition-all duration-1000" 
                             :style="{ width: genero.porcentaje + '%' }">
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center py-10">
                <p class="text-slate-500 uppercase font-bold text-xs tracking-widest">Aún no hay datos suficientes de géneros</p>
            </div>
        </div>
    </div>
</div>

            <!-- CONTENIDO: AJUSTES (SEGURIDAD) -->
            <div v-if="tabActiva === 'ajustes'" class="max-w-md">
                <div class="bg-[#191f31] p-8 rounded-3xl border border-white/5">
                    <h3 class="text-xl font-black text-white uppercase tracking-tighter mb-6">Cambiar Contraseña</h3>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label class="text-[10px] uppercase font-black text-slate-500 mb-2 block">Contraseña Actual</label>
                            <input v-model="passwordForm.current_password" type="password" class="w-full bg-[#0c1324] border-white/10 rounded-xl text-sm text-white focus:ring-red-600 focus:border-red-600">
                            <p v-if="passwordForm.errors.current_password" class="text-red-500 text-[10px] mt-1 uppercase font-bold">{{ passwordForm.errors.current_password }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-black text-slate-500 mb-2 block">Nueva Contraseña</label>
                            <input v-model="passwordForm.password" type="password" class="w-full bg-[#0c1324] border-white/10 rounded-xl text-sm text-white focus:ring-red-600 focus:border-red-600">
                            <p v-if="passwordForm.errors.password" class="text-red-500 text-[10px] mt-1 uppercase font-bold">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-black text-slate-500 mb-2 block">Confirmar Nueva Contraseña</label>
                            <input v-model="passwordForm.password_confirmation" type="password" class="w-full bg-[#0c1324] border-white/10 rounded-xl text-sm text-white focus:ring-red-600 focus:border-red-600">
                        </div>
                        <button type="submit" :disabled="passwordForm.processing" class="w-full py-4 bg-red-600 hover:bg-red-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all disabled:opacity-50">
                            Actualizar Seguridad
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </MainLayout>
</template>