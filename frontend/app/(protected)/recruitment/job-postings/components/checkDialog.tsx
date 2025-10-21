import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { JSX, ReactNode } from "react";
import { Info } from "lucide-react";
import { Input } from "@/components/ui/input";

/**
 * CheckDialog component
 * 
 * This component renders a confirmation dialog with different messages and styles
 * depending on the `action` prop. It supports three actions: "unpublish", "close", and "delete".
 * 
 * Props:
 *  - trigger: ReactNode - The element that triggers the dialog when clicked.
 *  - action: "unpublish" | "close" | "delete" - The action type to customize the dialog content and style.
 * 
 * Returns:
 *  JSX.Element - The rendered confirmation dialog.
 */
export default function CheckDialog({
    trigger,
    action,
}: {
    trigger: ReactNode;
    action: "unpublish" | "close" | "delete";
}): JSX.Element {
    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>

            <DialogTitle
                id={`check-dialog-title-${action}`}
                data-testid={`check-dialog-title-${action}`}
                className="sr-only"
            >
                {/* Title visually hidden since content below serves as dialog content */}
                {action === "unpublish"
                    ? "Unpublish Confirmation"
                    : action === "close"
                        ? "Close Confirmation"
                        : "Delete Confirmation"}
            </DialogTitle>

            <DialogContent
                className="w-[414px]"
                id={`check-dialog-content-${action}`}
                data-testid={`check-dialog-content-${action}`}
            >
                <div className="w-full flex flex-col items-center">
                    <div
                        className={`w-[40px] h-[40px] rounded-full ${action === "delete" ? "bg-[#C3060633]" : "bg-[#D6EEEC]"
                            } flex items-center justify-center`}
                        id={`check-dialog-icon-bg-${action}`}
                        data-testid={`check-dialog-icon-bg-${action}`}
                    >
                        <Info
                            className={`size-[20px] ${action === "delete" ? "text-[#C30606]" : "text-[#0d978b]"
                                }`}
                            id={`check-dialog-icon-${action}`}
                            data-testid={`check-dialog-icon-${action}`}
                        />
                    </div>
                </div>

                <p
                    className="mt-[18px] text-[#353535] text-[16px]/[20px] font-semibold text-center"
                    id={`check-dialog-title-text-${action}`}
                    data-testid={`check-dialog-title-text-${action}`}
                >
                    {action === "unpublish"
                        ? "Unpublish this Job?"
                        : action === "close"
                            ? "Close this Job Posting?"
                            : "Permanently Delete This Job?"}
                </p>

                <p
                    className="mt-[4px] text-[#787878] text-[14px]/[20px] text-center"
                    id={`check-dialog-description-${action}`}
                    data-testid={`check-dialog-description-${action}`}
                >
                    {action === "unpublish"
                        ? "Applicants will no longer be able to apply, but the job remains open internally and can be re-published from drafts anytime."
                        : action === "close"
                            ? "Applicants will no longer be able to apply, and the job will be marked as closed in your system."
                            : "This action will remove this job posting and all associated data. It cannot be undone! You can unpublish or close this job instead to remove temporarily."}
                </p>

                {action === "delete" && (
                    <Input
                        placeholder="Type DELETE to Confirm"
                        className="mt-[18px]"
                        id="check-dialog-delete-input"
                        data-testid="check-dialog-delete-input"
                    />
                )}

                <div
                    className="w-full flex justify-center gap-[12px] mt-[18px]"
                    id={`check-dialog-buttons-${action}`}
                    data-testid={`check-dialog-buttons-${action}`}
                >
                    <DialogClose asChild>
                        <Button
                            className="h-[36px]"
                            id={`check-dialog-cancel-button-${action}`}
                            data-testid={`check-dialog-cancel-button-${action}`}
                            variant="outline"
                        >
                            Cancel
                        </Button>
                    </DialogClose>

                    {action === "delete" ? (
                        <DialogClose asChild>
                            <Button
                                className="h-[36px] bg-[#C30606] hover:bg-[#C30606]/80"
                                id="check-dialog-delete-button"
                                data-testid="check-dialog-delete-button"
                            >
                                Delete Job
                            </Button>
                        </DialogClose>
                    ) : (
                        <DialogClose asChild>
                            <Button
                                className="h-[36px]"
                                id={`check-dialog-confirm-button-${action}`}
                                data-testid={`check-dialog-confirm-button-${action}`}
                            >
                                {action === "unpublish"
                                    ? "Unpublish"
                                    : action === "close"
                                        ? "Close Job"
                                        : "Delete"}
                            </Button>
                        </DialogClose>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
