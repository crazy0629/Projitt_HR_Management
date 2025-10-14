'use client';

import { Suspense, useCallback, useEffect, useState } from 'react';
import Link from 'next/link'; // fixed import path for Link
import { useRouter, useSearchParams } from 'next/navigation';
import { AlertCircle, LoaderCircleIcon } from 'lucide-react';
import { Alert, AlertIcon, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

export default function Page() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [message, setMessage] = useState<string | null>('Verifying...');
  const [error, setError] = useState<string | null>(null);

  const verify = useCallback(
    async (token: string) => {

    },
    [router]
  );

  useEffect(() => {
    const token = searchParams?.get('token');

    if (!token) {
      setMessage(null);
      setError('Invalid or missing token.');
      return;
    }

    verify(token);
  }, [searchParams, verify]);

  return (
    <Suspense>
      <div className="w-full space-y-6">
        <h1 className="text-2xl font-semibold">Email Verification</h1>

        {error && (
          <>
            <Alert variant="destructive">
              <AlertIcon>
                <AlertCircle />
              </AlertIcon>
              <AlertTitle>{error}</AlertTitle>
            </Alert>

            <Button asChild>
              <Link href="/signin" className="text-primary">
                Go back to Login
              </Link>
            </Button>
          </>
        )}

        {message && (
          <Alert>
            <AlertIcon>
              <LoaderCircleIcon className="w-4 h-4 animate-spin stroke-muted-foreground" />
            </AlertIcon>
            <AlertTitle>{message}</AlertTitle>
          </Alert>
        )}
      </div>
    </Suspense>
  );
}
