<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios'; 
import MainLayout from '@/Layouts/MainLayout.vue';

// Props que recibiremos de Laravel
const props = defineProps({
    movie: Object,
    session: Object,
    occupiedSeats: {
        type: Array,
        default: () => []
    },
    precioTicket: Number,
});

const asientosSeleccionados = ref([]);

const columnas = computed(() => parseInt(props.session.room?.columns) || 12);

const filas = computed(() => {
    const numFilas = props.session.room?.rows || 8; 
    return Array.from({ length: numFilas }, (_, i) => String.fromCharCode(65 + i));
});

const isOcupado = (id) => props.occupiedSeats.includes(id);
const isSeleccionado = (id) => asientosSeleccionados.value.includes(id);

const toggleAsiento = (id) => {
    if (isOcupado(id)) return;
    
    const index = asientosSeleccionados.value.indexOf(id);
    if (index > -1) {
        asientosSeleccionados.value.splice(index, 1);
    } else {
        asientosSeleccionados.value.push(id);
    }
};

const cuponCode = ref('');
const descuentoPorcentaje = ref(0);
const cuponMensaje = ref('');
const cuponError = ref(false);
const cuponId = ref(null); 

const aplicarCupon = async () => {
    if (!cuponCode.value) return;
    
    cuponMensaje.value = "Comprobando...";
    cuponError.value = false;

    try {
        const response = await axios.post('/validar-cupon', { code: cuponCode.value });
        if (response.data.valid) {
            descuentoPorcentaje.value = response.data.discount;
            cuponId.value = response.data.id;
            cuponError.value = false;
            cuponMensaje.value = `¡Cupón del ${descuentoPorcentaje.value}% aplicado!`;
        }
    } catch (error) {
        descuentoPorcentaje.value = 0;
        cuponId.value = null;
        cuponError.value = true;
        cuponMensaje.value = error.response?.data?.message || "Cupón inválido o agotado.";
    }
};

const quitarCupon = () => {
    cuponCode.value = '';
    descuentoPorcentaje.value = 0;
    cuponId.value = null;
    cuponMensaje.value = '';
};

const precioTotal = computed(() => {
    const precioBase = parseFloat(props.precioTicket || 0); 
    const totalSinDescuento = asientosSeleccionados.value.length * precioBase;
    
    if (descuentoPorcentaje.value > 0) {
        const descuento = totalSinDescuento * (descuentoPorcentaje.value / 100);
        return (totalSinDescuento - descuento).toFixed(2);
    }
    return totalSinDescuento.toFixed(2);
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
    
    router.post(`/sesion/${props.session.id}/comprar`, {
        asientos: asientosSeleccionados.value,
        precio_total: precioTotal.value,
        cupon_id: cuponId.value
    });
};
</script>

<template>
    <Head :title="`Asientos | ${movie.title}`" />

    <MainLayout>
        
        <main class="text-[#dce1fb] selection:bg-red-600 selection:text-white pt-24 md:pt-32 pb-24 px-4 md:px-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            
            <div class="lg:col-span-8 flex flex-col items-center">
                
                <div class="w-full max-w-2xl mb-12 md:mb-20 relative" style="perspective: 500px;">
                    <div class="h-1.5 md:h-2 w-full bg-gradient-to-b from-red-600 to-transparent rounded-full" 
                         style="transform: rotateX(-30deg); box-shadow: 0 -20px 50px -10px rgba(220, 38, 38, 0.3);"></div>
                    <p class="text-center text-[9px] md:text-[10px] uppercase tracking-[0.4em] font-bold text-slate-500 mt-4 md:mt-6">PANTALLA</p>
                </div>

                <div class="w-full overflow-x-auto py-4 md:py-8 px-2 md:px-6 flex justify-start md:justify-center no-scrollbar">
                    <div class="flex flex-col gap-2 md:gap-3 mb-6 md:mb-10 w-max mx-auto">
                        
                        <div v-for="fila in filas" :key="fila" class="flex gap-2 md:gap-3 justify-center items-center">
                            
                            <button 
                                v-for="col in columnas" :key="`${fila}${col}`"
                                @click="toggleAsiento(`${fila}${col}`)"
                                :disabled="isOcupado(`${fila}${col}`)"
                                :class="[
                                    'flex-none w-7 h-7 md:w-10 md:h-10 rounded-md md:rounded-lg flex items-center justify-center text-[9px] md:text-[10px] font-bold transition-all relative border border-white/5',
                                    isOcupado(`${fila}${col}`) 
                                        ? 'bg-[#121827] text-slate-700 cursor-not-allowed pointer-events-none'
                                        : isSeleccionado(`${fila}${col}`)
                                            ? 'bg-red-600 border-red-500 text-white shadow-[0_0_15px_rgba(220,38,38,0.8)] scale-110 z-20'
                                            : 'bg-[#191f31] hover:bg-slate-500 text-slate-400 cursor-pointer hover:z-10 hover:text-white'
                                ]"
                            >
                                <span v-if="isOcupado(`${fila}${col}`)" class="material-symbols-outlined text-[10px] md:text-xs">close</span>
                                <span v-else>{{ fila }}{{ col }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 md:gap-10 py-4 md:py-6 px-6 md:px-10 rounded-2xl md:rounded-full bg-[#191f31] border border-white/5 flex-wrap justify-center">
                    <div class="flex items-center gap-2 md:gap-3">
                        <div class="w-3 h-3 md:w-4 md:h-4 rounded bg-[#2e3447]"></div>
                        <span class="text-[9px] md:text-xs font-label uppercase tracking-widest text-slate-400">Disponible</span>
                    </div>
                    <div class="flex items-center gap-2 md:gap-3">
                        <div class="w-3 h-3 md:w-4 md:h-4 rounded bg-slate-800 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[8px] md:text-[10px] text-slate-600">close</span>
                        </div>
                        <span class="text-[9px] md:text-xs font-label uppercase tracking-widest text-slate-400">Ocupado</span>
                    </div>
                    <div class="flex items-center gap-2 md:gap-3">
                        <div class="w-3 h-3 md:w-4 md:h-4 rounded bg-red-600 shadow-[0_0_8px_rgba(220,38,38,0.5)]"></div>
                        <span class="text-[9px] md:text-xs font-label uppercase tracking-widest text-slate-400">Seleccionado</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 mt-8 lg:mt-0">
                
                <div class="sticky top-24 md:top-32 bg-[#191f31]/70 backdrop-blur-[20px] border-t border-[#ac8884]/30 rounded-3xl p-6 md:p-8 overflow-hidden relative shadow-2xl">
                    
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-red-600/10 blur-[80px] rounded-full pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="aspect-[16/9] w-full rounded-xl overflow-hidden mb-6 md:mb-8 group bg-[#2e3447]">
                            <img v-if="movie.backdrop_path" :src="`https://image.tmdb.org/t/p/w780${movie.backdrop_path}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        </div>
                        
                        <h1 class="font-display text-2xl md:text-3xl font-black tracking-tighter text-[#dce1fb] mb-2 uppercase break-words">{{ movie.title }}</h1>
                        
                        <div class="flex items-center gap-4 mb-6 md:mb-8">
                            <span class="px-2 py-1 bg-white/5 border border-white/10 rounded text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ session.room?.name || 'SALA ESTÁNDAR' }}</span>
                        </div>
                        
                        <div class="space-y-4 md:space-y-6 mb-6 md:mb-8 border-t border-white/5 pt-6 md:pt-8">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-label text-xs md:text-sm">Fecha</span>
                                <span class="text-[#dce1fb] text-sm md:text-base font-semibold capitalize">{{ formatearFecha(session.start_time) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-label text-xs md:text-sm">Hora</span>
                                <span class="text-[#dce1fb] text-sm md:text-base font-semibold">{{ formatearHora(session.start_time) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 font-label text-xs md:text-sm">Precio por entrada</span>
                                <div class="text-right">
                                    <span class="text-[#dce1fb] text-sm md:text-base font-semibold">{{ parseFloat(props.precioTicket).toFixed(2) }}€</span>
                                    <span v-if="new Date(session.start_time).getDay() === 3" class="block text-green-400 text-[9px] md:text-[10px] font-bold uppercase tracking-widest mt-1">
                                        ¡Día del espectador!
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center border-t border-white/5 pt-4">
                                <span class="text-slate-500 font-label text-xs md:text-sm">Asientos</span>
                                <div class="flex gap-1.5 md:gap-2 flex-wrap justify-end max-w-[60%]">
                                    <span v-if="asientosSeleccionados.length === 0" class="text-xs italic text-slate-600">Ninguno</span>
                                    <span v-for="asiento in asientosSeleccionados" :key="asiento" class="px-2 md:px-3 py-1 bg-red-600/20 text-[#ffb4ab] font-bold rounded-lg text-xs md:text-sm">
                                        {{ asiento }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- CUPÓN RESTAURADO -->
                        <div class="mb-6 md:mb-8 border-t border-white/5 pt-6">
                            <label class="text-slate-500 font-label text-[10px] md:text-xs uppercase tracking-widest mb-3 block">Código de descuento</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input 
                                    v-model="cuponCode" 
                                    :disabled="descuentoPorcentaje > 0"
                                    type="text" 
                                    placeholder="Ej: LUMIN10" 
                                    class="flex-1 bg-[#121827] border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder:text-slate-600 focus:border-red-500 focus:ring-0 uppercase transition-colors disabled:opacity-50"
                                    @keyup.enter="aplicarCupon"
                                >
                                <button 
                                    v-if="descuentoPorcentaje === 0"
                                    @click="aplicarCupon" 
                                    :disabled="!cuponCode"
                                    class="w-full sm:w-auto px-4 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl font-bold text-sm transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                    Comprobar
                                </button>
                                <button 
                                    v-else
                                    @click="quitarCupon" 
                                    class="w-full sm:w-auto px-4 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/20 rounded-xl font-bold text-sm transition-colors">
                                    Quitar
                                </button>
                            </div>
                            <p v-if="cuponMensaje" :class="['text-[9px] md:text-[10px] uppercase tracking-widest mt-3 font-bold', cuponError ? 'text-red-400' : 'text-green-400']">
                                {{ cuponMensaje }}
                            </p>
                        </div>
                        
                        <div class="flex justify-between items-end mb-8 md:mb-10 border-t border-white/5 pt-6">
                            <div>
                                <span class="block text-slate-500 font-label text-[10px] md:text-xs uppercase tracking-widest mb-1">Total a pagar</span>
                                <div class="flex items-center gap-2 md:gap-3">
                                    <div class="text-3xl md:text-4xl font-display font-black tracking-tight text-[#dce1fb]">{{ precioTotal }}€</div>
                                    <span v-if="descuentoPorcentaje > 0" class="px-2 py-1 bg-green-500/20 text-green-400 text-[9px] md:text-[10px] font-bold rounded uppercase tracking-wider border border-green-500/20">
                                        -{{ descuentoPorcentaje }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <button @click="confirmarReserva" :disabled="asientosSeleccionados.length === 0" 
                                class="w-full py-4 md:py-5 font-headline font-extrabold text-sm md:text-base uppercase tracking-widest rounded-2xl transition-all shadow-[0_10px_30px_-5px_rgba(220,38,38,0.4)] disabled:bg-[#2e3447] disabled:text-slate-500 disabled:shadow-none disabled:cursor-not-allowed bg-red-600 hover:bg-red-700 text-white active:scale-95 flex justify-center items-center gap-2">
                            Proceder al pago
                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </button>
                        
                        <p @click="asientosSeleccionados = []" class="text-center text-slate-600 text-[9px] md:text-[10px] uppercase font-bold tracking-[0.2em] mt-6 cursor-pointer hover:text-slate-400 transition-colors">
                            Limpiar asientos
                        </p>
                    </div>
                </div>
            </div>
            
        </main>
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>