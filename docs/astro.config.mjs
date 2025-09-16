// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';
import starlightThemeRapide from 'starlight-theme-rapide';
import { viewTransitions } from "astro-vtbot/starlight-view-transitions";
import starlightChangelogs from 'starlight-changelogs';

// https://astro.build/config
export default defineConfig({
	integrations: [
		starlight({
			// Brand
			title: 'Good PHP Reflection',
			logo: {
				src: './src/assets/logo.png',
			},
			favicon: '/favicon.png',
			social: [{ icon: 'github', label: 'GitHub', href: 'https://github.com/good-php/reflection' }],

			// Settings
			plugins: [
				starlightThemeRapide(),
				viewTransitions(),
				starlightChangelogs(),
			],

			// Content
			sidebar: [
				{
					label: 'Getting Started',
					items: [
						{ label: 'Installation', link: '/' },
						{ slug: 'getting-started/usage' },
						{ slug: 'getting-started/supported-features' },
						{ label: 'Changelog', link: 'changelog' },
					],
				},
				{
					label: 'Type System',
					items: [
						{ slug: 'type-system/overview' },
						{ label: 'Types', autogenerate: { directory: 'type-system/types' } },
						{ slug: 'type-system/comparing-types' },
					],
				},
				{
					label: 'Internals',
					items: [
						{ slug: 'internals/adding-and-overriding-definitions' },
						{ slug: 'internals/contributing' },
					],
				},
			],
		}),
	],
});
