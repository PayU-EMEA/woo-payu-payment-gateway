const CardFieldErrors = ( { errors } ) => {
	return (
		<div className="payu-sf-validation-error">
			{ errors &&
				Array.isArray( errors ) &&
				errors.map( ( value ) => value.message ).concat( ' ' ) }
		</div>
	);
};
export default CardFieldErrors;
