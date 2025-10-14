'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { zodResolver } from '@hookform/resolvers/zod';
import { EyeOff } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { LoaderCircleIcon } from 'lucide-react';
import { ChangePasswordSchemaType, getChangePasswordSchema } from '../forms/change-password-schema';
import { Label } from '@/components/ui/label';
import FooterWithCompanyLogo from '../component/footerWithCompayLogo';

/**
 * Page component for changing/resetting user password after token verification.
 */
export default function Page() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams?.get('token') ?? null;

  const [verifyingToken, setVerifyingToken] = useState(false);
  const [isValidToken, setIsValidToken] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [passwordConfirmationVisible, setPasswordConfirmationVisible] = useState(false);

  const form = useForm<ChangePasswordSchemaType>({
    resolver: zodResolver(getChangePasswordSchema()),
    defaultValues: {
      newPassword: '',
      confirmPassword: '',
    },
  });

  useEffect(() => {
    async function verifyToken() {

    }

    if (token) {
      verifyToken();
    } else {
      setError('No reset token provided.');
    }
  }, [token]);

  async function onSubmit(values: ChangePasswordSchemaType) {
    setIsProcessing(true);
    setError(null);
    setSuccessMessage(null);

    // For now, we simulate navigation immediately:
    router.push('/confirm-email');

    /*
    try {
      const response = await fetch('/api/auth/change-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, newPassword: values.newPassword }),
      });

      if (response.ok) {
        setSuccessMessage('Password reset successful! Redirecting to login...');
        setTimeout(() => router.push('/signin'), 3000);
      } else {
        const data = await response.json();
        setError(data.message || 'Password reset failed.');
      }
    } catch {
      setError('An error occurred while resetting the password.');
    } finally {
      setIsProcessing(false);
    }
    */
  }

  return (
    <>
      <div className="w-full h-full flex flex-col justify-between gap-[30px]">
        <div className="pt-[60px] flex justify-center w-full">
          <img src="/images/logo.png" alt="logo" className="h-[48px]" />
        </div>
        <div className="w-full flex-1 flex justify-center items-center pb-[10px]">
          <div className="w-[482px] border border-[#e9e9e9] rounded-[16px] bg-white py-[40px] px-[40px]">
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="block w-full space-y-4">
                <div>
                  <h1 className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535]">
                    Set up your Account
                  </h1>
                  <p className="text-[14px]/[20px] mt-[10px] text-[#4B4B4B]">
                    Create your login credentials to get started with Projitt.
                  </p>
                </div>

                <Label className="text-[14px]/[22px] font-normal text-[#353535]">
                  Email Address
                </Label>
                <Input
                  type="email"
                  disabled
                  value="admin@zaidllc.com"
                  className="h-[48px] mt-[10px]"
                />

                <FormField
                  control={form.control}
                  name="newPassword"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-[14px]/[22px] font-normal text-[#353535]">
                        New Password
                      </FormLabel>
                      <div className="relative">
                        <FormControl>
                          <Input
                            type={passwordVisible ? 'text' : 'password'}
                            placeholder="Enter new password"
                            {...field}
                            className="h-[48px]"
                            autoComplete="new-password"
                          />
                        </FormControl>
                        <Button
                          type="button"
                          variant="ghost"
                          mode="icon"
                          onClick={() => setPasswordVisible(!passwordVisible)}
                          className="absolute end-0 top-1/2 -translate-y-1/2 h-7 w-7 me-1.5 bg-transparent!"
                          aria-label={passwordVisible ? 'Hide password' : 'Show password'}
                        >
                          {passwordVisible ? (
                            <EyeOff className="text-muted-foreground" />
                          ) : (
                            <img src="/images/icons/eye.svg" alt="eye" className="w-[15px]" />
                          )}
                        </Button>
                      </div>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="confirmPassword"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-[14px]/[22px] font-normal text-[#353535]">
                        Confirm New Password
                      </FormLabel>
                      <div className="relative">
                        <FormControl>
                          <Input
                            type={passwordConfirmationVisible ? 'text' : 'password'}
                            placeholder="Confirm new password"
                            {...field}
                            className="h-[48px]"
                            autoComplete="new-password"
                          />
                        </FormControl>
                        <Button
                          type="button"
                          variant="ghost"
                          mode="icon"
                          onClick={() =>
                            setPasswordConfirmationVisible(!passwordConfirmationVisible)
                          }
                          className="absolute end-0 top-1/2 -translate-y-1/2 h-7 w-7 me-1.5 bg-transparent!"
                          aria-label={
                            passwordConfirmationVisible
                              ? 'Hide password confirmation'
                              : 'Show password confirmation'
                          }
                        >
                          {passwordConfirmationVisible ? (
                            <EyeOff className="text-muted-foreground" />
                          ) : (
                            <img src="/images/icons/eye.svg" alt="eye" className="w-[15px]" />
                          )}
                        </Button>
                      </div>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <Button
                  type="submit"
                  disabled={isProcessing}
                  className="w-full h-[48px] text-[14px]/[20px] font-semibold"
                >
                  {isProcessing && <LoaderCircleIcon className="size-4 animate-spin" />}
                  Confirm
                </Button>
              </form>
            </Form>
          </div>
        </div>
        <FooterWithCompanyLogo />
      </div>
    </>
  );
}
