const ReadMore = ( { children, onCLick } ) => {
  return (
    <span
      className="payu-read-more"
      onClick={ onCLick }
      onKeyDown={ onCLick }
      role="button"
      tabIndex={ 0 }
    >
      { children }
    </span>
  );
};

export default ReadMore;
