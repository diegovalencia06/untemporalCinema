<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    // Sustituimos route('login') por el texto '/login'
    form.post('/login', { 
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="min-h-screen bg-[#0c1324] flex flex-col justify-center items-center p-6 text-white font-sans">
        
        <div class="w-full max-w-md bg-[#191f31] border border-white/10 rounded-3xl p-10 shadow-2xl">
            
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black uppercase tracking-widest">Luminous</h1>
                <p class="text-slate-400 text-sm mt-2">Acceso al Sistema</p>
            </div>

            <div v-if="status" class="mb-4 text-green-400 text-sm text-center font-bold">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Email</label>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        class="w-full bg-[#0c1324] border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
                        required
                    >
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-2">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Contraseña</label>
                    <input 
                        type="password" 
                        v-model="form.password" 
                        class="w-full bg-[#0c1324] border border-white/10 rounded-xl px-4 py-3 text-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none"
                        required
                    >
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-2">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.remember" class="rounded bg-[#0c1324] border-white/10 text-red-600">
                        <span class="text-slate-400">Recordarme</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black uppercase tracking-widest transition-all disabled:opacity-50"
                >
                    Entrar
                </button>
                <div class="text-center mt-6">
                <p class="text-slate-400 text-sm">
                    ¿Eres nuevo? 
                    <Link href="/register" class="text-red-500 font-bold hover:underline ml-1">Crea una cuenta</Link>
                </p>
            </div>
            </form>
        </div>
    </div>
</template>