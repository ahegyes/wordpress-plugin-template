// wp-scripts bundles the styles a block imports, so TypeScript only needs to know the imports exist.
declare module '*.scss';

// @wordpress/block-editor ships no type declarations, so the hooks the blocks use and the store the editor script
// dispatches to are declared here.
declare module '@wordpress/block-editor' {
	import type { BlockInstance } from '@wordpress/blocks';
	import type { ReduxStoreConfig, StoreDescriptor } from '@wordpress/data';
	import type { HTMLAttributes } from 'react';

	export const store: StoreDescriptor<
		ReduxStoreConfig<
			unknown,
			{
				insertBlocks: (
					blocks: BlockInstance | BlockInstance[]
				) => object;
			},
			unknown
		>
	>;

	export function useBlockProps(
		props?: HTMLAttributes< HTMLElement >
	): HTMLAttributes< HTMLElement >;
}
