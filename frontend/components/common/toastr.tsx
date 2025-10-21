import { toast } from 'sonner';
import {
  Alert,
  AlertIcon,
  AlertTitle,
  AlertDescription,
  AlertContent,
} from '@/components/ui/alert';

export const customToast = (
  title: string,
  description: string,
  variant: 'success' | 'error' | 'warning' | 'info'
) => {
  toast.custom(
    () => (
      <Alert
        close={true}
        onClose={() => toast.dismiss()}
        className="bg-white border border-[#e9e9e9] flex items-center gap-[10px] justify-between"
      >
        <div className="flex items-center gap-[12px]">
          <AlertIcon>
            <img
              src={`/images/icons/toastr-${variant}.svg`}
              alt="error"
              className="w-[32px] h-[32px]"
            />
          </AlertIcon>
          <AlertContent className="space-y-0">
            <AlertTitle className="text-[14px]/[20px] font-medium text-[#1c1c1c]">
              {title}
            </AlertTitle>
            <AlertDescription className="text-[12px]/[18px] font-normal text-[#626262]">
              {description}
            </AlertDescription>
          </AlertContent>
        </div>
      </Alert>
    ),
    {
      position: 'top-right',
    }
  );
};
