'use client';

import { ReactNode } from 'react';
import { TooltipProvider } from '@/components/ui/tooltip';

/**
 * TooltipsProvider Component
 *
 * This component wraps its children with a TooltipProvider that configures global tooltips
 * to appear instantly (zero delay). Useful in enhancing UX consistency across the app.
 */
export function TooltipsProvider({
  children,
}: {
  children: ReactNode;
}): JSX.Element {
  return (
    <TooltipProvider
      delayDuration={0}
      id="global-tooltips-wrapper"
      data-testid="global-tooltips-wrapper"
    >
      {children}
    </TooltipProvider>
  );
}
