export default function Error({ status, message }) {
  return (
    <div style={{ padding: 24 }}>
      <h1>Er is iets misgegaan</h1>
      {status && <p>Status: {status}</p>}
      <p>{message || 'Probeer het opnieuw of neem contact op met de helpdesk'}</p>
    </div>
  );
}
