'use client';

/**
 * Reset Password Page
 *
 * This page allows a user to set a new password using a token from a password reset link.
 * It provides form validation, password visibility toggles, and API integration.
 */

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams, useParams } from 'next/navigation';
import { zodResolver } from '@hookform/resolvers/zod';
import { AlertCircle, Check, Eye, EyeOff, LoaderCircleIcon } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { Alert, AlertIcon, AlertTitle } from '@/components/ui/alert';
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
import { ChangePasswordSchemaType, getChangePasswordSchema } from '../../forms/change-password-schema';
import { resetPassword } from '@/api/user';
import { customToast } from '@/components/common/toastr';
import FooterWithCompanyLogo from '../../component/footerWithCompayLogo';

export default function Page() {
  const router = useRouter();
  const { token } = useParams();

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
      email: '',
      newPassword: '',
      confirmPassword: '',
    },
  });

  /**
   * Handles form submission to reset the password.
   *
   * @param values - Validated form values
   * @returns Promise<void>
   */
  async function onSubmit(values: ChangePasswordSchemaType): Promise<void> {
    setIsProcessing(true);
    setError(null);
    setSuccessMessage(null);

    try {
      if (!token) {
        customToast('Error', 'Token not found', 'error');
        return;
      }

      const response = await resetPassword({
        token: token as string,
        email: values.email,
        password: values.newPassword,
      });

      if (response?.data?.status === false) {
        customToast('Error', response?.data?.error, 'error');
        return;
      }

      customToast('Success', 'Password reset successful. Redirecting to login...', 'success');
      setTimeout(() => router.push('/signin'), 3000);
    } catch (err: any) {
      customToast('Error', err?.response?.data?.message || 'Something went wrong', 'error');
    } finally {
      setIsProcessing(false);
    }
  }

  return (
    <div
      className="w-full h-full flex flex-col justify-between gap-[30px]"
      id="reset-password-wrapper"
      data-testid="reset-password-wrapper"
    >
      <div className="pt-[60px] flex justify-center w-full">
        <img
          src="/images/zaidLLC.png"
          alt="logo"
          className="h-[48px]"
          id="logo-image"
          data-testid="logo-image"
        />
      </div>

      <div className="w-full flex-1 flex justify-center items-center pb-[10px]">
        <div
          className="w-[482px] border border-[#e9e9e9] rounded-[16px] bg-white py-[40px] px-[40px]"
          id="reset-password-card"
          data-testid="reset-password-card"
        >
          <Form {...form}>
            <form
              onSubmit={form.handleSubmit(onSubmit)}
              className="block w-full space-y-4"
              id="reset-password-form"
              data-testid="reset-password-form"
            >
              <div className="space-y-1">
                <h1
                  className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535]"
                  id="form-title"
                  data-testid="form-title"
                >
                  Set your New Password
                </h1>
              </div>

              {/* Token verification, error and success feedback (currently commented out) */}
              {/* {error && (
                <div className="text-center space-y-6">
                  <Alert variant="destructive">
                    <AlertIcon><AlertCircle /></AlertIcon>
                    <AlertTitle>{error}</AlertTitle>
                  </Alert>
                  <Button asChild><Link href="/signin" className="text-primary">Go back to Login</Link></Button>
                </div>
              )}

              {successMessage && (
                <Alert>
                  <AlertIcon><Check /></AlertIcon>
                  <AlertTitle>{successMessage}</AlertTitle>
                </Alert>
              )}

              {verifyingToken && (
                <Alert>
                  <AlertIcon><LoaderCircleIcon className="size-4 animate-spin" /></AlertIcon>
                  <AlertTitle>Verifing...</AlertTitle>
                </Alert>
              )} */}

              {/* Form Fields */}
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-[14px]/[22px] font-normal text-[#353535]">
                      Email Address
                    </FormLabel>
                    <FormControl>
                      <Input
                        {...field}
                        id="email-input"
                        data-testid="email-input"
                        placeholder="Enter email address"
                        className="h-[48px]"
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
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
                          {...field}
                          type={passwordVisible ? 'text' : 'password'}
                          id="new-password-input"
                          data-testid="new-password-input"
                          placeholder="Enter new password"
                          className="h-[48px]"
                        />
                      </FormControl>
                      <Button
                        type="button"
                        variant="ghost"
                        mode="icon"
                        onClick={() => setPasswordVisible(!passwordVisible)}
                        className="absolute end-0 top-1/2 -translate-y-1/2 h-7 w-7 me-1.5 bg-transparent!"
                        aria-label={passwordVisible ? 'Hide password' : 'Show password'}
                        id="toggle-password-visibility"
                        data-testid="toggle-password-visibility"
                      >
                        {passwordVisible ? (
                          <EyeOff className="text-muted-foreground" />
                        ) : (
                          <img
                            src="/images/icons/eye.svg"
                            alt="eye"
                            className="w-[15px]"
                          />
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
                          {...field}
                          type={passwordConfirmationVisible ? 'text' : 'password'}
                          id="confirm-password-input"
                          data-testid="confirm-password-input"
                          placeholder="Confirm new password"
                          className="h-[48px]"
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
                        id="toggle-confirm-password-visibility"
                        data-testid="toggle-confirm-password-visibility"
                      >
                        {passwordConfirmationVisible ? (
                          <EyeOff className="text-muted-foreground" />
                        ) : (
                          <img
                            src="/images/icons/eye.svg"
                            alt="eye"
                            className="w-[15px]"
                          />
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
                id="submit-reset-password"
                data-testid="submit-reset-password"
              >
                {isProcessing && <LoaderCircleIcon className="size-4 animate-spin mr-2" />}
                Confirm
              </Button>
            </form>
          </Form>
        </div>
      </div>

      <FooterWithCompanyLogo />
    </div>
  );
}
