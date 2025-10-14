'use client';

/**
 * Password Reset Page
 *
 * This page handles the UI and logic for resetting a user's password using a token sent via email.
 * It includes form validation, password visibility toggles, and visual feedback for success/failure.
 */

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Button } from '@/components/ui/button';

export default function Page() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams?.get('token') || null;

  const [verifyingToken, setVerifyingToken] = useState(false);
  const [isValidToken, setIsValidToken] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  /**
   * Effect to verify reset token from URL query.
   * This would typically hit an API to verify token validity.
   */
  useEffect(() => {
    const verifyToken = async () => {
      // try {
      //   setVerifyingToken(true);
      //   const response = await apiFetch('/api/auth/reset-password-verify', {
      //     method: 'POST',
      //     headers: { 'Content-Type': 'application/json' },
      //     body: JSON.stringify({ token }),
      //   });
      //   if (response.ok) {
      //     setIsValidToken(true);
      //   } else {
      //     const errorData = await response.json();
      //     setError(errorData.message || 'Invalid or expired token.');
      //   }
      // } catch {
      //   setError('Unable to verify the reset token.');
      // } finally {
      //   setVerifyingToken(false);
      // }
    };

    if (token) {
      verifyToken();
    } else {
      setError('No reset token provided.');
    }
  }, [token]);



  return (
    <div className="w-full h-full flex flex-col justify-between overflow-y-auto gap-[56px]">
      <div className="flex flex-col flex-1 gap-[56px]">
        <div className="h-[95px] border-b border-[#e9e9e9] w-full flex items-center justify-center">
          <img
            src="/images/logo.png"
            alt="logo"
            className="h-[40px]"
            id="logo-image"
            data-testid="logo-image"
          />
        </div>

        <div className="w-full h-full flex justify-center items-center px-[10px]">
          <div
            className="w-[622px] border border-[#e9e9e9] rounded-[16px] bg-white py-[63px] px-[40px] flex flex-col items-center"
            id="reset-success-container"
            data-testid="reset-success-container"
          >
            <div className="relative w-[100px] h-[100px] flex items-center justify-center">
              <div className="absolute w-[100px] h-[100px] rounded-full bg-[#0D978B33] ripple"></div>
              <div className="absolute w-[70px] h-[70px] rounded-full bg-[#0D978B] opacity-[20%] ripple delay-300"></div>
              <div className="relative z-10 flex items-center justify-center">
                <img
                  src="/images/icons/check-double.svg"
                  alt="check-icon"
                  className="w-[40px] h-[40px]"
                  id="check-success-icon"
                  data-testid="check-success-icon"
                />
              </div>
            </div>

            <p
              className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535] text-center mt-[13px]"
              id="reset-success-title"
              data-testid="reset-success-title"
            >
              You’re All Set!
            </p>
            <p
              className="text-[18px]/[30px] mt-[19px] text-[#4B4B4B] text-center"
              id="reset-success-subtext1"
              data-testid="reset-success-subtext1"
            >
              Your Projitt workspace is now ready based on your selections.
            </p>
            <p
              className="text-[18px]/[30px] text-[#4B4B4B] text-center"
              id="reset-success-subtext2"
              data-testid="reset-success-subtext2"
            >
              You can start adding employees, configuring workflows, and managing your HR
              operations with ease.
            </p>

            <div className="flex justify-center">
              <Button
                className="w-[284px] h-[48px] font-semibold text-[16px]/[24px] mt-[36px]"
                id="go-to-dashboard-button"
                data-testid="go-to-dashboard-button"
              >
                Go to Dashboard
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div
        className="px-[48px] pb-[50px] flex sm:flex-row flex-col items-center gap-[10px] justify-between pt-[20px]"
        id="footer-legal"
        data-testid="footer-legal"
      >
        <div className="flex gap-[16px]">
          <span
            className="text-[14px]/[22px] underline text-[#a19e9e]"
            id="footer-terms"
            data-testid="footer-terms"
          >
            Terms of Service
          </span>
          <div className="w-[1px] h-[20px] bg-[#a19e9e]"></div>
          <span
            className="text-[14px]/[22px] underline text-[#a19e9e]"
            id="footer-privacy"
            data-testid="footer-privacy"
          >
            Privacy Policy
          </span>
        </div>

        <span
          className="text-[14px]/[22px] text-[#a19e9e]"
          id="footer-copyright"
          data-testid="footer-copyright"
        >
          © 2025 Projitt
        </span>
      </div>
    </div>
  );
}
