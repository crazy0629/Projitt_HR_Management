'use client';

import { JSX, useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { zodResolver } from '@hookform/resolvers/zod';
import { EyeOff } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { getSigninSchema, SigninSchemaType } from '../forms/signin-schema';
import { customToast } from '@/components/common/toastr';
import { login } from '@/api/user';
import { useSession } from '@/context/SessionContext';
import axios from 'axios';
import FooterWithCompanyLogo from '../component/footerWithCompayLogo';

/**
 * Page component renders the user login interface and handles login logic.
 * It includes form validation, submission handling, session management,
 * and UI state for password visibility and loading status.
 * 
 * @returns JSX.Element - Login page component
 */
export default function Page(): JSX.Element {
  const router = useRouter();
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const { setSession } = useSession();

  // Initialize react-hook-form with validation schema and default values
  const form = useForm<SigninSchemaType>({
    resolver: zodResolver(getSigninSchema()),
    defaultValues: {
      email: "",
      password: "",
      rememberMe: false,
    },
  });

  /**
   * On component mount, attempt to load saved user info from localStorage
   * and pre-fill the form fields if present.
   */
  useEffect(() => {
    const userInfoRaw = localStorage.getItem("userInfo");
    if (userInfoRaw) {
      try {
        const userInfo = JSON.parse(userInfoRaw);
        form.reset({
          email: userInfo.email || "",
          password: userInfo.password || "",
          rememberMe: userInfo.rememberMe || false,
        });
      } catch (e) {
        console.error("Invalid localStorage JSON", e);
      }
    }
  }, [form]);

  /**
   * Handles form submission for login.
   * Calls the login API and processes response for authentication.
   * Saves user info to localStorage if 'remember me' is checked.
   * Sets axios authorization header on success and navigates to home.
   * Displays error toast on failure.
   * 
   * @param values - Form values conforming to SigninSchemaType
   * @returns Promise<void>
   */
  async function onSubmit(values: SigninSchemaType): Promise<void> {
    setIsProcessing(true);
    setError(null);
    try {
      const response = await login({ email: values.email, password: values.password });
      if (response?.data?.status === false) {
        customToast("Error", response?.data?.error, "error");
        return;
      }
      setSession({ token: response?.data?.data?.token || "", authenticated: true });
      if (values.rememberMe) {
        localStorage.setItem("userInfo", JSON.stringify(values));
      } else {
        localStorage.removeItem("userInfo");
      }
      axios.defaults.headers.common['Authorization'] = `Bearer ${response?.data?.data?.token}`;
      router.push("/");
    } catch (err: any) {
      customToast("Error", err.response?.data?.message || "An error occurred", "error");
    } finally {
      setIsProcessing(false);
    }
  }

  return (
    <>
      <div className="w-full h-full flex flex-col justify-between gap-[30px]">
        {/* Logo Section */}
        <div className="pt-[60px] flex justify-center w-full">
          <img
            src="/images/zaidLLC.png"
            alt="logo"
            className="h-[48px]"
            id="login-page-logo"
            data-testid="login-page-logo"
          />
        </div>

        {/* Login Form Container */}
        <div className="w-full flex-1 flex justify-center items-center pb-[10px]">
          <div
            className="w-[482px] border border-[#e9e9e9] rounded-[16px] bg-white py-[48px] px-[48px]"
            id="login-form-container"
            data-testid="login-form-container"
          >
            <Form {...form}>
              <form
                onSubmit={form.handleSubmit(onSubmit)}
                className="block w-full space-y-5"
                id="login-form"
                data-testid="login-form"
              >
                {/* Form Title */}
                <div className="pb-[24px] mb-0">
                  <h1
                    className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535]"
                    id="login-form-title"
                    data-testid="login-form-title"
                  >
                    Log in to your account
                  </h1>
                </div>

                {/* Email Input Field */}
                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel
                        className="text-[14px]/[22px] font-normal text-[#353535]"
                        htmlFor="email-input"
                      >
                        Email Address
                      </FormLabel>
                      <FormControl>
                        <Input
                          id="email-input"
                          data-testid="email-input"
                          placeholder="Enter email address"
                          {...field}
                          className="h-[48px]"
                          type="email"
                          autoComplete="email"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Password Input Field with Visibility Toggle */}
                <FormField
                  control={form.control}
                  name="password"
                  render={({ field }) => (
                    <FormItem>
                      <div className="flex justify-between items-center gap-2.5">
                        <FormLabel
                          className="text-[14px]/[22px] font-normal text-[#353535]"
                          htmlFor="password-input"
                        >
                          Password
                        </FormLabel>
                      </div>
                      <div className="relative">
                        <Input
                          id="password-input"
                          data-testid="password-input"
                          placeholder="Enter password"
                          type={passwordVisible ? 'text' : 'password'}
                          {...field}
                          className="h-[48px]"
                          autoComplete="current-password"
                        />
                        <Button
                          type="button"
                          variant="ghost"
                          mode="icon"
                          size="sm"
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
                              alt="Show password"
                              className="w-[15px]"
                              id="eye-icon"
                              data-testid="eye-icon"
                            />
                          )}
                        </Button>
                      </div>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                {/* Remember Me Checkbox and Forgot Password Link */}
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    <FormField
                      control={form.control}
                      name="rememberMe"
                      render={({ field }) => (
                        <>
                          <Checkbox
                            id="remember-me"
                            data-testid="remember-me-checkbox"
                            checked={field.value}
                            onCheckedChange={(checked) => field.onChange(!!checked)}
                            className="cursor-pointer"
                          />
                          <label
                            htmlFor="remember-me"
                            className="text-[14px]/[20px] leading-none text-[#626262] cursor-pointer"
                            id="remember-me-label"
                            data-testid="remember-me-label"
                          >
                            Remember me on this device
                          </label>
                        </>
                      )}
                    />
                  </div>
                  <Link
                    href="/reset-password"
                    className="text-[14px]/[20px] font-medium text-[#0d978b] hover:text-[#0d978b]"
                    id="forgot-password-link"
                    data-testid="forgot-password-link"
                  >
                    Forgot Password?
                  </Link>
                </div>

                {/* Submit Button */}
                <div className="flex flex-col gap-2.5">
                  <Button
                    type="submit"
                    disabled={isProcessing}
                    className="h-[48px]"
                    id="sign-in-button"
                    data-testid="sign-in-button"
                  >
                    {isProcessing ? <LoaderCircleIcon className="size-4 animate-spin" /> : null}
                    Sign In
                  </Button>
                </div>
              </form>
            </Form>
          </div>
        </div>

        {/* Footer */}
        <FooterWithCompanyLogo />
      </div>
    </>
  );
}
