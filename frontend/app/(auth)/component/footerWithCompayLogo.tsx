import { JSX } from "react";

/**
 * FooterWithCompanyLogo component renders the footer section with company logo,
 * links to Terms of Service and Privacy Policy, and copyright information.
 *
 * @returns {JSX.Element} The rendered footer component
 */
const FooterWithCompanyLogo = (): JSX.Element => {
    return (
        <div id="footer-with-logo" data-testid="footer-with-logo">
            <div
                className="w-full flex justify-center md:mb-[-25px] mb-0"
                id="footer-logo-container"
                data-testid="footer-logo-container"
            >
                <img
                    src="/images/poweredBy.png"
                    alt="logo"
                    className="h-[28px]"
                    id="footer-logo-image"
                    data-testid="footer-logo-image"
                />
            </div>
            <div
                className="w-full pb-[50px] pl-[80px] pr-[80px] flex md:flex-row flex-col items-center justify-between gap-[10px] md:mt-0 mt-[10px]"
                id="footer-links-container"
                data-testid="footer-links-container"
            >
                <div className="flex gap-[16px]" id="footer-links" data-testid="footer-links">
                    <span
                        className="text-[14px]/[22px] underline text-[#a19e9e]"
                        id="terms-of-service-link"
                        data-testid="terms-of-service-link"
                    >
                        Terms of Service
                    </span>
                    <div
                        className="w-[1px] h-[20px] bg-[#a19e9e]"
                        id="footer-divider"
                        data-testid="footer-divider"
                    />
                    <span
                        className="text-[14px]/[22px] underline text-[#a19e9e]"
                        id="privacy-policy-link"
                        data-testid="privacy-policy-link"
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
};

export default FooterWithCompanyLogo;
