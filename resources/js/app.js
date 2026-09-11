import './bootstrap';
import { createApp, h, onMounted, onUnmounted } from 'vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import { createPinia } from 'pinia';
import router from './router';
import { RouterView } from 'vue-router';
import VerificationAccessDialog from './components/VerificationAccessDialog.vue';
import 'primeicons/primeicons.css';
import '../css/app.css';

const Root = {
	setup() {
		const toast = useToast();
		const handleToast = (event) => toast.add(event.detail);
		onMounted(() => window.addEventListener('app:toast', handleToast));
		onUnmounted(() => window.removeEventListener('app:toast', handleToast));

		return () => h('div', [h(Toast, { class: 'global-toast', position: 'top-center' }), h(VerificationAccessDialog), h(RouterView)]);
	},
};

createApp(Root)
	.use(createPinia())
	.use(PrimeVue)
	.use(ToastService)
	.use(router)
	.mount('#app');
