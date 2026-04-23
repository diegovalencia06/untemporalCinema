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
    <Head title="Registro - Luminous" />

    <div class="min-h-screen bg-[#0c1324] flex flex-col justify-center items-center p-6 text-white font-sans">
        
        <div class="w-full max-w-md bg-[#191f31] border border-white/10 rounded-3xl p-10 shadow-2xl">
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black uppercase tracking-widest text-white">Luminous</h1>
                <p class="text-slate-400 text-sm mt-2">Crea tu cuenta de espectador</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Nombre Completo</label>
                    <input 
                        type="text" 
                        v-model="form.name" 
                        class="w-full bg-[#0c1324] border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all"
                        required
                        autofocus
                    >
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Email</label>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        class="w-full bg-[#0c1324] border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all"
                        required
                    >
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Contraseña</label>
                    <input 
                        type="password" 
                        v-model="form.password" 
                        class="w-full bg-[#0c1324] border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all"
                        required
                    >
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        v-model="form.password_confirmation" 
                        class="w-full bg-[#0c1324] border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all"
                        required
                    >
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black uppercase tracking-widest transition-all disabled:opacity-50 mt-4"
                >
                    Registrarse
                </button>

                <div class="text-center mt-6">
                    <p class="text-slate-400 text-sm">
                        ¿Ya tienes cuenta? 
                        <Link href="/login" class="text-red-500 font-bold hover:underline ml-1">Inicia sesión aquí</Link>
                    </p>
                </div>
            </form>
        </div>
    </div>
</template>