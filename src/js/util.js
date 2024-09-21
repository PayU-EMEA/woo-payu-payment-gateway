const scriptUtil = ( src ) => {
	let script = null;

	const remove = () => {
		script?.parentNode?.removeChild( script );
	};

	const load = () => {
		return new Promise( ( resolve, reject ) => {
			const handleOnLoad = () => {
				script?.setAttribute( 'data-loaded', 'true' );
				resolve();
			};

			const handleOnError = () => {
				remove();
				reject( new Error( `Unable to load script [${ src }]` ) );
			};

			const bodyContainer = document.querySelector( 'body' );

			if ( ! bodyContainer ) {
				reject( new Error( 'Missing <body>' ) );

				return;
			}

			script = bodyContainer.querySelector( `script[src="${ src }"]` );

			if ( script?.getAttribute( 'data-loaded' ) ) {
				resolve();

				return;
			}

			script = document.createElement( 'script' );
			script.src = src;
			script.async = true;

			script.addEventListener( 'load', handleOnLoad );
			script.addEventListener( 'error', handleOnError );

			bodyContainer.appendChild( script );
		} );
	};

	return {
		load,
		remove,
	};
};

export { scriptUtil };
