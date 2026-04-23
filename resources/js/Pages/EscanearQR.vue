<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Html5QrcodeScanner } from 'html5-qrcode';
import axios from 'axios';

const props = defineProps({
    sessionId: String, // Recibimos el ID de la sesión si venimos de Filament
    sessionInfo: Object
});

const scanResult = ref(null); // 'ok', 'used', 'invalid'
const scanMessage = ref('');
const seatInfo = ref('');
const isScanning = ref(true);

let scanner = null;

onMounted(() => {
    iniciarEscaner();
});

onUnmounted(() => {
    if (scanner) {
        scanner.clear();
    }
});

const iniciarEscaner = () => {
    isScanning.value = true;
    scanResult.value = null;
    
    // Configuramos el lector
    scanner = new Html5QrcodeScanner(
        "qr-reader", 
        { fps: 10, qrbox: { width: 250, height: 250 } }, 
        false
    );

    scanner.render(onScanSuccess);
};

const onScanSuccess = async (decodedText) => {
    // Pausamos el escáner para no leer el mismo QR 20 veces
    if (!isScanning.value) return;
    isScanning.value = false;
    scanner.clear(); // Ocultamos la cámara mientras mostramos el resultado

    try {
        const response = await axios.post('/api/escanear-qr', {
            qr_code: decodedText,
            session_id: props.sessionId
        });

        scanResult.value = response.data.status;
        scanMessage.value = response.data.message;
        seatInfo.value = response.data.seat || '';

    } catch (error) {
        scanResult.value = 'invalid';
        scanMessage.value = 'Error de conexión con el servidor.';
    }
};

const resetear = async () => {
    // 1. Limpiamos las variables
    scanResult.value = null;
    scanMessage.value = '';
    seatInfo.value = '';
    isScanning.value = true;
    
    // 2. Esperamos obligatoriamente a que Vue pinte el HTML de nuevo
    await nextTick();
    
    // 3. Ahora sí, arrancamos la cámara con seguridad
    iniciarEscaner();
};
</script>

<template>
    <Head title="Control de Acceso" />

    <div class="min-h-screen bg-[#0c1324] text-white flex flex-col items-center justify-center p-6">
        
        <div class="w-full max-w-lg bg-[#191f31] border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            
            <div class="p-6 text-center border-b border-white/10">
                <template v-if="sessionInfo">
                    {{ sessionInfo.pelicula }} <br>
                    <span class="text-lg text-slate-400">
                        {{ sessionInfo.hora }} | {{ sessionInfo.sala }}
                    </span>
                </template>
            </div>

            <div v-if="isScanning" class="p-6 bg-white">
                <div id="qr-reader" class="w-full text-black"></div>
            </div>

            <div v-else class="p-10 flex flex-col items-center text-center transition-all"
                 :class="{
                     'bg-green-500': scanResult === 'ok',
                     'bg-yellow-400 text-black': scanResult === 'used',
                     'bg-red-600': scanResult === 'invalid'
                 }">
                
                <span class="material-symbols-outlined text-7xl mb-4 font-black">
                    {{ scanResult === 'ok' ? 'check_circle' : (scanResult === 'used' ? 'warning' : 'cancel') }}
                </span>
                
                <h2 class="text-4xl font-black uppercase tracking-widest mb-4">
                    {{ scanResult === 'ok' ? 'OK - ADELANTE' : (scanResult === 'used' ? 'YA USADO' : 'NO VÁLIDO') }}
                </h2>
                
                <p class="text-xl font-bold opacity-90">{{ scanMessage }}</p>

                <div v-if="seatInfo" class="mt-6 px-6 py-2 bg-black/20 rounded-full font-mono text-2xl font-bold">
                    Asiento: {{ seatInfo }}
                </div>

                <button @click="resetear" 
                        class="mt-10 px-8 py-4 bg-black/30 hover:bg-black/50 text-white rounded-xl font-bold uppercase tracking-widest transition-all">
                    Escanear Siguiente
                </button>
            </div>
            
        </div>
    </div>
</template>

<style>
/* Pequeño parche para que el HTML5-QRCode no se vea feo */
#qr-reader { border: none !important; }
#qr-reader button { background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; font-weight: bold; margin-top: 10px; cursor: pointer; }
#qr-reader__dashboard_section_csr span { color: #000 !important; }
</style>