<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Html5QrcodeScanner } from 'html5-qrcode';
import axios from 'axios';

const props = defineProps({
    sessionId: String,
    sessionInfo: Object
});

const scanResult = ref(null);
const scanMessage = ref('');
const seatInfo = ref('');
const isScanning = ref(true);

const canProcessScan = ref(false); 

let scanner = null;
let timeoutId = null;

onMounted(() => {
    iniciarEscaner();
});

onUnmounted(() => {
    if (scanner) {
        scanner.clear();
    }
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});

const iniciarEscaner = () => {
    isScanning.value = true;
    scanResult.value = null;
    canProcessScan.value = false; 

    scanner = new Html5QrcodeScanner(
        "qr-reader", 
        { fps: 5, qrbox: { width: 250, height: 250 } }, 
        false
    );

    scanner.render(onScanSuccess);

    timeoutId = setTimeout(() => {
        canProcessScan.value = true;
    }, 1500); 
};

const onScanSuccess = async (decodedText) => {
    if (!isScanning.value || !canProcessScan.value) return;
    
    isScanning.value = false;
    
    // AÑADE ESTAS DOS LÍNEAS AQUÍ:
    scanResult.value = 'loading'; 
    scanMessage.value = 'Verificando entrada...'; 

    scanner.clear();

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
    scanResult.value = null;
    scanMessage.value = '';
    seatInfo.value = '';
    isScanning.value = true;
    
    await nextTick();
    
    iniciarEscaner(); // Esto volverá a disparar los 1.5 segundos de retraso
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
                     'bg-blue-600': scanResult === 'loading', /* NUEVO COLOR PARA CARGA */
                     'bg-green-500': scanResult === 'ok',
                     'bg-yellow-400 text-black': scanResult === 'used',
                     'bg-red-600': scanResult === 'invalid'
                 }">
                
                <!-- SE AÑADE EL ICONO DE 'sync' Y LA ANIMACIÓN DE GIRO -->
                <span class="material-symbols-outlined text-7xl mb-4 font-black" :class="{'animate-spin': scanResult === 'loading'}">
                    {{ scanResult === 'loading' ? 'sync' : (scanResult === 'ok' ? 'check_circle' : (scanResult === 'used' ? 'warning' : 'cancel')) }}
                </span>
                
                <!-- SE AÑADE EL TEXTO DE 'CARGANDO...' -->
                <h2 class="text-4xl font-black uppercase tracking-widest mb-4">
                    {{ scanResult === 'loading' ? 'CARGANDO...' : (scanResult === 'ok' ? 'OK - ADELANTE' : (scanResult === 'used' ? 'YA USADO' : 'NO VÁLIDO')) }}
                </h2>
                
                <p class="text-xl font-bold opacity-90">{{ scanMessage }}</p>

                <!-- OCULTAMOS ASIENTO Y BOTÓN MIENTRAS CARGA -->
                <div v-if="seatInfo && scanResult !== 'loading'" class="mt-6 px-6 py-2 bg-black/20 rounded-full font-mono text-2xl font-bold">
                    Asiento: {{ seatInfo }}
                </div>

                <button v-if="scanResult !== 'loading'" @click="resetear" 
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
