<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

// Props que recibiremos de Laravel
const props = defineProps({
    movie: Object,
    session: Object,
    occupiedSeats: {
        type: Array,
        default: () => [] // Por ahora un array vacío, o los que envíe el servidor
    }
});

// --- LÓGICA DE ASIENTOS ---
const filas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
const columnas = 12;
const asientosSeleccionados = ref([]);

// Funciones para comprobar estados
const isOcupado = (id) => props.occupiedSeats.includes(id);
const isSeleccionado = (id) => asientosSeleccionados.value.includes(id);

// Función para hacer click en un asiento
const toggleAsiento = (id) => {
    if (isOcupado(id)) return; // Si está ocupado, no hace nada
    
    const index = asientosSeleccionados.value.indexOf(id);
    if (index > -1) {
        asientosSeleccionados.value.splice(index, 1); // Lo quita
    } else {
        asientosSeleccionados.value.push(id); // Lo añade
    }
};

// Precio total calculado al momento
const precioTotal = computed(() => {
    // Asegúrate de que base_price existe en tu tabla sessions
    const precioBase = parseFloat(props.session.base_price || 8.50); 
    return (asientosSeleccionados.value.length * precioBase).toFixed(2);
});

// Formatear Fecha y Hora
const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: 'numeric' });
};
const formatearHora = (fecha) => {
    return new Date(fecha).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
};

// Enviar al servidor
const confirmarReserva = () => {
    if (asientosSeleccionados.value.length === 0) {
        alert("Por favor, selecciona al menos un asiento.");
        return;
    }
    
    // Aquí enviaremos los datos a la pasarela de pago o a guardar la reserva
    console.log("Comprando asientos:", asientosSeleccionados.value);
    
    /* Descomenta esto cuando tengas el backend listo:
    router.post('/reserva', {
        session_id: props.session.id,
        seats: asientosSeleccionados.value
    });
    */
};
</script>

<template>
    <div class="min-h-screen bg-[#0c1324] text-[#dce1fb] font-body selection:bg-red-600 selection:text-white">
        
        <main class="pt-32 pb-24 px-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            <div class="lg:col-span-8 flex flex-col items-center">
                
                <div class="w-full max-w-2xl mb-20 relative" style="perspective: 500px;">
                    <div class="h-2 w-full bg-gradient-to-b from-red-600 to-transparent rounded-full" 
                         style="transform: rotateX(-30deg); box-shadow: 0 -20px 50px -10px rgba(220, 38, 38, 0.3);"></div>
                    <p class="text-center text-[10px] uppercase tracking-[0.4em] font-bold text-slate-500 mt-6">PANTALLA</p>
                </div>

                <div class="w-full overflow-x-auto pb-4 px-2 flex justify-center">
                    <div class="flex flex-col gap-3 mb-16 w-max">
                        
                        <div v-for="fila in filas" :key="fila" class="flex gap-3 justify-center items-center">
                            <button 
                                v-for="col in columnas" :key="`${fila}${col}`"
                                @click="toggleAsiento(`${fila}${col}`)"
                                :disabled="isOcupado(`${fila}${col}`)"
                                :class="[
                                    'flex-none w-8 h-8 md:w-10 md:h-10 rounded-lg flex items-center justify-center text-[10px] font-bold transition-all relative',
                                    isOcupado(`${fila}${col}`) 
                                        ? 'bg-slate-800/50 text-slate-700 cursor-not-allowed pointer-events-none'
                                        : isSeleccionado(`${fila}${col}`)
                                            ? 'bg-red-600 text-white shadow-[0_0_15px_rgba(220,38,38,0.6)] ring-2 ring-red-600 scale-110 z-10'
                                            : 'bg-[#2e3447] hover:bg-slate-500 hover:text-white cursor-pointer hover:z-10'
                                ]"
                            >
                                <span v-if="isOcupado(`${fila}${col}`)" class="material-symbols-outlined text-xs">close</span>
                                <span v-else>{{ fila }}{{ col }}</span>
                            </button>
                        </div>
                        
                    </div>
                </div>

                <div class="flex gap-10 py-6 px-10 rounded-full bg-[#191f31] border border-white/5 flex-wrap justify-center">
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-[#2e3447]"></div>
                        <span class="text-xs font-label uppercase tracking-widest text-slate-400">Disponible</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-slate-800 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[10px] text-slate-600">close</span>
                        </div>
                        <span class="text-xs font-label uppercase tracking-widest text-slate-400">Ocupado</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 rounded bg-red-600 shadow-[0_0_8px_rgba(220,38,38,0.5)]"></div>
                        <span class="text-xs font-label uppercase tracking-widest text-slate-400">Seleccionado</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4">
                
                <div class="sticky top-32 bg-[#191f31]/70 backdrop-blur-[20px] border-t border-[#ac8884]/30 rounded-3xl p-8 overflow-hidden relative shadow-2xl">
                    
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-red-600/10 blur-[80px] rounded-full pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="aspect-[16/9] w-full rounded-xl overflow-hidden mb-8 group bg-[#2e3447]">
                            <img v-if="movie.backdrop_path" :src="`https://image.tmdb.org/t/p/w780${movie.backdrop_path}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        </div>
                        
                        <h1 class="font-display text-3xl font-black tracking-tighter text-[#dce1fb] mb-2 uppercase">{{ movie.title }}</h1>
                        
                        <div class="flex items-center gap-4 mb-8">
                            <span class="px-2 py-1 bg-white/5 border border-white/10 rounded text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ session.room?.name || 'SALA ESTÁNDAR' }}</span>
                        </div>
                        
                        <div class="space-y-6 mb-10 border-t border-white/5 pt-8">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-label text-sm">Fecha</span>
                                <span class="text-[#dce1fb] font-semibold capitalize">{{ formatearFecha(session.start_time) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-label text-sm">Hora</span>
                                <span class="text-[#dce1fb] font-semibold">{{ formatearHora(session.start_time) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-white/5 pt-4">
                                <span class="text-slate-500 font-label text-sm">Asientos seleccionados</span>
                                <div class="flex gap-2 flex-wrap justify-end">
                                    <span v-if="asientosSeleccionados.length === 0" class="text-xs italic text-slate-600">None</span>
                                    <span v-for="asiento in asientosSeleccionados" :key="asiento" class="px-3 py-1 bg-red-600/20 text-[#ffb4ab] font-bold rounded-lg text-sm">
                                        {{ asiento }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-end mb-10">
                            <div>
                                <span class="block text-slate-500 font-label text-xs uppercase tracking-widest mb-1">Precio total</span>
                                <div class="text-4xl font-display font-black tracking-tight text-[#dce1fb]">{{ precioTotal }}€</div>
                            </div>
                            <div class="text-slate-500 text-xs font-label italic pb-1">IVA incluido</div>
                        </div>
                        
                        <button @click="confirmarReserva" :disabled="asientosSeleccionados.length === 0" 
                                class="w-full py-5 font-headline font-extrabold uppercase tracking-widest rounded-2xl transition-all shadow-[0_10px_30px_-5px_rgba(220,38,38,0.4)] disabled:bg-[#2e3447] disabled:text-slate-500 disabled:shadow-none disabled:cursor-not-allowed bg-red-600 hover:bg-red-700 text-white active:scale-95">
                            Proceder al pago
                        </button>
                        
                        <p @click="asientosSeleccionados = []" class="text-center text-slate-600 text-[10px] uppercase font-bold tracking-[0.2em] mt-6 cursor-pointer hover:text-slate-400 transition-colors">
                            Cancelar compra
                        </p>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
</template>