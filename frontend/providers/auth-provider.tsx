'use client';

import { Session } from 'next-auth';
import { SessionProvider } from 'next-auth/react';

interface AuthProviderProps {
  children: React.ReactNode;
  session?: Session | null;
}

/**
 * AuthProvider wraps the application with NextAuth's SessionProvider,
 * passing the session and configuring the base path for authentication routes.
 *
 * @param {Object} props
 * @param {React.ReactNode} props.children - Child components to be wrapped
 * @param {Session | null} [props.session] - Optional current user session
 * @returns JSX.Element - The SessionProvider wrapping children
 */
export function AuthProvider({ children, session }: AuthProviderProps): JSX.Element {
  const basePath =
    (typeof window !== 'undefined'
      ? process.env.NEXT_PUBLIC_BASE_PATH
      : process.env.NEXT_PUBLIC_BASE_PATH) || '';
  return (
    <SessionProvider session={session} basePath={`${basePath}/user`}>
      {children}
    </SessionProvider>
  );
}
