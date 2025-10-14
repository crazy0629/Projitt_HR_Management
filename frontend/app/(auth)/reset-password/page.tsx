'use client';

import { JSX, Suspense, useState } from 'react';
import Link from 'next/link';
import { zodResolver } from '@hookform/resolvers/zod';
import { AlertCircle, Check } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
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
import { LoaderCircleIcon } from 'lucide-react';
import { forgotPasssword } from '@/api/user';
import { customToast } from '@/components/common/toastr';
import FooterWithCompanyLogo from '../component/footerWithCompayLogo';

/**
 * Page component renders the Reset Password page with an email input form.
 * On submission, it triggers the forgot password process and shows success or error alerts.
 *
 * @returns JSX.Element - The Reset Password page UI
 */
export default function Page(): JSX.Element {
  const [error, setError] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);
  const [checking, setChecking] = useState(false);
  const [email, setEmail] = useState<string | null>(null);
  const [showRecaptcha, setShowRecaptcha] = useState(false); // unused but preserved as requested

  const formSchema = z.object({
    email: z.string().email({ message: 'Please enter a valid email address.' }),
  });

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: '',
    },
  });

  /**
   * Handles the form submit event.
   * Validates the email and calls the forgot password API.
   * Shows toast notifications for errors and updates the UI on success.
   *
   * @param e - The form submission event
   * @returns Promise<void>
   */
  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>): Promise<void> => {
    e.preventDefault();
    const result = await form.trigger();
    if (!result) return;
    try {
      const values = form.getValues();
      setIsProcessing(true);
      const response = await forgotPasssword({ email: values.email });
      if (response?.data?.status === false) {
        customToast("Error", response?.data?.error, "error");
        return;
      }
      setEmail(values.email);
      setChecking(true);
      form.reset();
    } catch (err: any) {
      customToast("Error", err.response?.data?.message ?? 'Something went wrong', "error");
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <>
      <div className="w-full h-full flex flex-col justify-between gap-[30px]">
        <div className="pt-[60px] flex justify-center w-full">
          <img
            src="/images/zaidLLC.png"
            alt="logo"
            className="h-[48px]"
            id="company-logo"
            data-testid="company-logo"
          />
        </div>

        <div className="w-full flex-1 flex justify-center items-center pb-[10px]">
          <div
            id="reset-password-form-container"
            className="w-[482px] border border-[#e9e9e9] rounded-[16px] bg-white py-[40px] px-[40px]"
          >
            {!checking ? (
              <Suspense>
                <Form {...form}>
                  <form onSubmit={handleSubmit} className="block w-full space-y-5" data-testid="reset-password-form">
                    <div className="space-y-1 pb-3">
                      <h1
                        id="reset-password-title"
                        className="text-[22px]/[30px] font-semibold tracking-tight"
                      >
                        Reset Password
                      </h1>
                      <p
                        id="reset-password-description"
                        className="text-[14px]/[26px] text-[#787878]"
                      >
                        Enter your account’s email address and we’ll send you a secure link to reset
                        your password.
                      </p>
                    </div>

                    {error && (
                      <Alert
                        id="error-alert"
                        variant="destructive"
                        onClose={() => setError(null)}
                        data-testid="error-alert"
                      >
                        <AlertIcon>
                          <AlertCircle />
                        </AlertIcon>
                        <AlertTitle>{error}</AlertTitle>
                      </Alert>
                    )}

                    {success && (
                      <Alert
                        id="success-alert"
                        onClose={() => setSuccess(null)}
                        data-testid="success-alert"
                      >
                        <AlertIcon>
                          <Check />
                        </AlertIcon>
                        <AlertTitle>{success}</AlertTitle>
                      </Alert>
                    )}

                    <FormField
                      control={form.control}
                      name="email"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel
                            htmlFor="input-email"
                            className="text-[14px]/[22px] text-[#353535]"
                          >
                            Email Address
                          </FormLabel>
                          <FormControl>
                            <Input
                              id="input-email"
                              data-testid="input-email"
                              type="email"
                              placeholder="Enter your email address"
                              disabled={!!success || isProcessing}
                              className="h-[48px]"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />

                    <Button
                      id="submit-button"
                      data-testid="submit-button"
                      type="submit"
                      disabled={!!success || isProcessing}
                      className="w-full h-[48px] text-[14px]/[20px] font-semibold"
                    >
                      {isProcessing ? <LoaderCircleIcon className="animate-spin" /> : null}
                      Continue
                    </Button>

                    <div className="flex justify-center">
                      <span
                        id="back-to-signin"
                        className="text-[14px]/[22px] text-[#626262] font-semibold text-center"
                      >
                        <Link href="/signin" data-testid="signin-link">
                          Back
                        </Link>
                      </span>
                    </div>
                  </form>
                </Form>
              </Suspense>
            ) : (
              <div
                id="check-email-message"
                className="flex justify-center flex-col items-center gap-[18px]"
                data-testid="check-email-message"
              >
                <img
                  src="/images/icons/streamline-stickies-color_mail.svg"
                  alt="mail"
                  className="w-[56px]"
                  id="email-icon"
                />
                <div className="flex flex-col items-center gap-[8px]">
                  <h1 className="text-[22px]/[30px] font-semibold text-[#353535]" id="check-email-title">
                    Check your Email
                  </h1>
                  <p className="text-[18px]/[30px] text-[#4b4b4b] text-center" id="check-email-description">
                    We’ve sent you a secure link to reset your password at{' '}
                    <span className="text-[#0d978b]" id="sent-email-address">
                      {email}
                    </span>
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>

        <FooterWithCompanyLogo />
      </div>
    </>
  );
}
