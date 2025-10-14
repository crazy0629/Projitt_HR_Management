'use client';

import { ReactNode } from 'react';
import { useSession } from '@/context/SessionContext';
import Loading from '@/components/common/loading';

interface LayoutProps {
  children: ReactNode;
}

export default function Layout({ children }: LayoutProps) {
  const { loading } = useSession();

  if (loading) {
    return <Loading />;
  }

  return (
    <div className="w-[100vw] h-[100vh] bg-[#fafafa] relative overflow-y-auto overflow-x-hidden">
      {children}
    </div>
  );
}
