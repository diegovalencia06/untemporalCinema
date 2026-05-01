<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post('/email/verification-notification');
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verificar Correo" />

    <div class="min-h-screen bg-[#0c1324] flex flex-col justify-center items-center p-6 font-sans">
        
        <div class="w-full max-w-md bg-surface-container-low border border-white/5 rounded-2xl p-10 shadow-2xl">
            
            <div class="text-center mb-8">
                <span class="material-symbols-outlined text-5xl text-primary mb-4 block">mail</span>
                <h1 class="font-headline text-2xl font-extrabold tracking-tight uppercase text-on-surface">Verifica tu correo</h1>
            </div>

            <div class="mb-6 text-sm text-on-surface-variant leading-relaxed text-center">
                ¡Gracias por registrarte! Antes de poder elegir tus asientos, por favor verifica tu dirección de correo electrónico haciendo clic en el enlace que te acabamos de enviar. Si no has recibido el correo, te enviaremos otro con gusto.
            </div>

            <div class="mb-6 font-medium text-sm text-green-400 text-center bg-green-400/10 p-3 rounded-lg border border-green-400/20" v-if="verificationLinkSent">
                Se ha enviado un nuevo enlace de verificación a la dirección de correo que proporcionaste en el registro.
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-4 bg-primary-container text-on-primary-container rounded-xl font-headline font-bold uppercase tracking-widest shadow-[0_10px_30px_rgba(220,38,38,0.4)] ring-2 ring-primary/30 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50"
                >
                    Reenviar correo
                </button>
                
                <div class="flex items-center justify-between mt-6 pt-6 border-t border-white/5">
                    <Link
                        href="/"
                        class="text-on-surface-variant hover:text-primary text-[10px] font-headline font-bold uppercase tracking-widest transition-colors"
                    >
                        Volver al inicio
                    </Link>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-on-surface-variant hover:text-red-500 text-[10px] font-headline font-bold uppercase tracking-widest transition-colors"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>