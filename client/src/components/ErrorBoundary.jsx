import { Component } from "react";

export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, info) {
    console.error("UI error:", error, info);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="flex min-h-screen items-center justify-center bg-paper p-6">
          <div className="max-w-md rounded-3xl border border-line bg-card p-8 text-center shadow-soft">
            <h1 className="text-2xl font-semibold text-ink">Something went wrong</h1>
            <p className="mt-3 text-sm text-muted">
              The menu board hit an unexpected problem. Please refresh the page. If this keeps happening, clear the site data for this browser and try again.
            </p>
            <button
              type="button"
              className="tap mt-6 rounded-full bg-pine px-6 py-3 text-sm font-semibold text-invert"
              onClick={() => window.location.reload()}
            >
              Refresh page
            </button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}
