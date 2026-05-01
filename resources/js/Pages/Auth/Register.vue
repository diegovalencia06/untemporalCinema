<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro" />

    <div class="min-h-screen bg-[#0c1324] flex flex-col justify-center items-center p-6 font-sans">
        
        <div class="w-full max-w-md bg-surface-container-low border border-white/5 rounded-2xl p-10 shadow-2xl">
            
            <div class="text-center mb-8">
                <h1 class="font-headline text-3xl font-extrabold tracking-tight uppercase text-on-surface">Untemporal Cinema</h1>
                <p class="text-on-surface-variant text-sm mt-2">Regístrate</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block font-headline text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-2">Nombre Completo</label>
                    <input 
                        type="text" 
                        v-model="form.name" 
                        class="w-full bg-surface-container border border-white/5 rounded-xl px-4 py-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        required
                        autofocus
                    >
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block font-headline text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-2">Email</label>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        class="w-full bg-surface-container border border-white/5 rounded-xl px-4 py-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        required
                    >
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block font-headline text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-2">Contraseña</label>
                    <input 
                        type="password" 
                        v-model="form.password" 
                        class="w-full bg-surface-container border border-white/5 rounded-xl px-4 py-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        required
                    >
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block font-headline text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-2">Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        v-model="form.password_confirmation" 
                        class="w-full bg-surface-container border border-white/5 rounded-xl px-4 py-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                        required
                    >
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-4 bg-primary-container text-on-primary-container rounded-xl font-headline font-bold uppercase tracking-widest shadow-[0_10px_30px_rgba(220,38,38,0.4)] ring-2 ring-primary/30 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 mt-4"
                >
                    Registrarse
                </button>

                <div class="text-center mt-6">
                    <p class="text-on-surface-variant text-sm">
                        ¿Ya tienes cuenta? 
                        <Link href="/login" class="text-primary font-bold hover:underline ml-1 transition-colors">Inicia sesión aquí</Link>
                    </p>
                </div>

                <div class="mt-8 pt-6 border-t border-white/5 text-center">
                    <Link href="/" class="inline-flex items-center justify-center gap-2 text-on-surface-variant hover:text-primary text-[10px] font-headline font-bold uppercase tracking-widest transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver a la cartelera
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>