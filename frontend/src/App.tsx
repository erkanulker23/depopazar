import { useEffect } from 'react';
import { BrowserRouter } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AppRoutes } from './routes/AppRoutes';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { useAuthStore } from './stores/authStore';
import { useCompanyStore } from './stores/companyStore';

const SEO_SUFFIX = ' - Depo Takip & CRM Sistemi';

function DocumentTitle() {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const projectName = useCompanyStore((s) => s.projectName);
  const publicBrand = useCompanyStore((s) => s.publicBrand);

  useEffect(() => {
    const name =
      isAuthenticated
        ? projectName
        : (publicBrand?.project_name?.trim() || 'DepoPazar');
    document.title = name + SEO_SUFFIX;
  }, [isAuthenticated, projectName, publicBrand]);

  return null;
}

function AppContent() {
  const loadPublicBrand = useCompanyStore((s) => s.loadPublicBrand);

  useEffect(() => {
    loadPublicBrand();
  }, [loadPublicBrand]);

  return (
    <>
      <DocumentTitle />
      <AppRoutes />
      <Toaster position="top-center" toastOptions={{ duration: 4000 }} />
    </>
  );
}

function App() {
  return (
    <BrowserRouter>
      <ThemeProvider>
        <AuthProvider>
          <AppContent />
        </AuthProvider>
      </ThemeProvider>
    </BrowserRouter>
  );
}

export default App;
