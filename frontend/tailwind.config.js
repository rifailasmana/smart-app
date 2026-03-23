/** @type {import('tailwindcss').Config} */
export default {
	content: [
		'./components/**/*.{js,vue,ts}',
		'./layouts/**/*.vue',
		'./pages/**/*.vue',
		'./plugins/**/*.{js,ts}',
		'./app.vue',
		'./error.vue',
	],
	theme: {
		extend: {
			fontFamily: {
				sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
			},
			colors: {
				brand: {
					sidebar: '#062e22',
					header: '#062e22',
					orange: '#FF8C00',
					yellow: '#FFC107',
					content: '#e5d3bf',
				},
				terminal: {
					bg: '#e5d3bf',
					panel: '#ffffff',
					sidebar: '#111827',
					accent: '#F97316',
					warning: '#FBBF24',
					danger: '#EF4444',
					text: '#1E293B',
					muted: '#64748B',
					border: '#374151',
				},
			},
			animation: {
				'fade-in': 'fadeIn 0.4s ease',
				'slide-right': 'slideRight 0.3s ease',
				'zoom-in': 'zoomIn 0.3s ease',
			},
			keyframes: {
				fadeIn: { from: { opacity: 0 }, to: { opacity: 1 } },
				slideRight: { from: { transform: 'translateX(20px)', opacity: 0 }, to: { transform: 'none', opacity: 1 } },
				zoomIn: { from: { transform: 'scale(0.95)', opacity: 0 }, to: { transform: 'none', opacity: 1 } },
			},
		},
	},
	plugins: [],
}
