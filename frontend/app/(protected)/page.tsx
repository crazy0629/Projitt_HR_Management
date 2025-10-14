import {
    ArrowRight,
    ArrowUpRight,
    BanknoteArrowUp,
    CalendarClock,
    LockKeyhole,
    Star,
    UserRoundPlus,
} from "lucide-react";
import { JSX } from "react";

/**
 * Dashboard component displays various modules with features,
 * their subscription status, and actions to manage or activate them.
 *
 * @returns JSX.Element - The dashboard UI with module cards and feature lists.
 */
export default function Dashboard(): JSX.Element {
    const modules = [
        {
            title: "HR Management",
            actionText: "Manage Subscriptions",
            features: [
                {
                    name: "Talent Management",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    tag: "Most Popular",
                    Icon: Star,
                },
                {
                    name: "Payroll & Tax",
                    description: "Manage your team talent and exponetial growth",
                    status: "Subscribed",
                    Icon: BanknoteArrowUp,
                },
                {
                    name: "Leave & Attendance",
                    description: "Manage your team talent and exponetial growth",
                    status: "Subscribed",
                    Icon: CalendarClock,
                },
                {
                    name: "Recruitment",
                    description: "Manage your team talent and exponetial growth",
                    status: "Subscribed",
                    Icon: UserRoundPlus,
                },
            ],
        },
        {
            title: "Vendor & Contract Management",
            actionText: "Activate",
            features: [
                {
                    name: "Vendor Management",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "E-Procurement",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Invoicing & Payment",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Smart Contract",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
            ],
        },
        {
            title: "Asset & Facility Management",
            actionText: "Activate",
            features: [
                {
                    name: "Asset Management",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Space Utilization",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Work Orders",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Reporting Center",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
            ],
        },
        {
            title: "Financial Management",
            actionText: "Activate",
            features: [
                {
                    name: "Financial Planning Setup",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Project Execution",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Revenue and Billing",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
                {
                    name: "Tax and Compliance",
                    description: "Manage your team talent and exponetial growth",
                    status: "Not subscribed",
                    Icon: LockKeyhole,
                },
            ],
        },
    ];

    return (
        <div className="flex flex-col">
            <p
                className="text-[24px]/[30px] text-[#1C1C1C] font-semibold"
                id="dashboard-title"
                data-testid="dashboard-title"
            >
                Home
            </p>
            <p
                className="text-[14px]/[20px] text-[#353535] font-[500]"
                id="dashboard-subtitle"
                data-testid="dashboard-subtitle"
            >
                Explore your active modules, discover more features, and configure your
                subscription.
            </p>
            <div
                className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-[31px]"
                id="modules-grid"
                data-testid="modules-grid"
            >
                {modules.map((item, index) => (
                    <div
                        className="bg-white rounded-[12px] border border-[#e9e9e9] pt-[24px] px-[24px]"
                        key={index}
                        id={`module-card-${index}`}
                        data-testid={`module-card-${index}`}
                    >
                        <div
                            className="flex w-full justify-between"
                            id={`module-header-${index}`}
                            data-testid={`module-header-${index}`}
                        >
                            <p
                                className="text-[14px]/[16px] text-[#4b4b4b] font-[500]"
                                id={`module-title-${index}`}
                                data-testid={`module-title-${index}`}
                            >
                                {item.title}
                            </p>
                            <button
                                className="text-[12px]/[16px] text-[#0d978b] font-[500] flex items-center gap-[8px] cursor-pointer"
                                id={`module-action-${index}`}
                                data-testid={`module-action-${index}`}
                                type="button"
                            >
                                <p>{item.actionText}</p>
                                <ArrowUpRight className="size-[15px]" />
                            </button>
                        </div>
                        <div className="mt-[32px]" id={`features-list-${index}`} data-testid={`features-list-${index}`}>
                            {item.features.map(({ Icon, ...feature }, i) => (
                                <div
                                    className="flex gap-[17px] items-center"
                                    key={i}
                                    id={`feature-${index}-${i}`}
                                    data-testid={`feature-${index}-${i}`}
                                >
                                    {feature.status === "Not subscribed" ? (
                                        <LockKeyhole
                                            className="size-[20px] mt-[-36px] text-[#8F8F8F]"
                                            id={`feature-icon-lock-${index}-${i}`}
                                            data-testid={`feature-icon-lock-${index}-${i}`}
                                        />
                                    ) : (
                                        <Icon
                                            className="size-[20px] mt-[-36px] text-[#0D978B]"
                                            id={`feature-icon-${index}-${i}`}
                                            data-testid={`feature-icon-${index}-${i}`}
                                        />
                                    )}
                                    <div
                                        className={`flex-1 flex justify-between border-b border-[#e9e9e9] pb-[16px] mb-[16px] ${i === item.features.length - 1 ? "mb-0 border-b-0 pb-0" : ""
                                            }`}
                                        id={`feature-content-${index}-${i}`}
                                        data-testid={`feature-content-${index}-${i}`}
                                    >
                                        <div className="flex w-full justify-between">
                                            <div
                                                className="w-[243px] text-[#8f8f8f]"
                                                id={`feature-text-${index}-${i}`}
                                                data-testid={`feature-text-${index}-${i}`}
                                            >
                                                <p
                                                    className={`text-[12px]/[16px] font-[500] ${feature.status === "Not subscribed"
                                                            ? "text-[#8F8F8F]"
                                                            : "text-[#353535]"
                                                        }`}
                                                    id={`feature-name-${index}-${i}`}
                                                    data-testid={`feature-name-${index}-${i}`}
                                                >
                                                    {feature.name}{" "}
                                                    {feature.tag === "Most Popular" && (
                                                        <span
                                                            className="text-[#0D978B] bg-[#d6eeec] p-[4px] rounded-[4px] ml-[12px] text-[10px]/[12px]"
                                                            id={`feature-tag-${index}-${i}`}
                                                            data-testid={`feature-tag-${index}-${i}`}
                                                        >
                                                            Most Popular
                                                        </span>
                                                    )}
                                                </p>
                                                <p
                                                    className="text-[10px]/[16px] mt-[2px]"
                                                    id={`feature-description-${index}-${i}`}
                                                    data-testid={`feature-description-${index}-${i}`}
                                                >
                                                    {feature.description}
                                                </p>
                                                <p
                                                    className={`text-[10px]/[16px] mt-[4px] font-semibold ${feature.status === "Not subscribed"
                                                            ? "text-[#8F8F8F]"
                                                            : "text-[#0D978B]"
                                                        }`}
                                                    id={`feature-status-${index}-${i}`}
                                                    data-testid={`feature-status-${index}-${i}`}
                                                >
                                                    {feature.status}
                                                </p>
                                            </div>
                                            {feature.status === "Not subscribed" ? (
                                                <button
                                                    className="text-[#053834] text-[12px]/[16px] cursor-pointer"
                                                    id={`learn-more-button-${index}-${i}`}
                                                    data-testid={`learn-more-button-${index}-${i}`}
                                                    type="button"
                                                >
                                                    Learn More
                                                </button>
                                            ) : (
                                                <button
                                                    className="cursor-pointer"
                                                    id={`arrow-right-button-${index}-${i}`}
                                                    data-testid={`arrow-right-button-${index}-${i}`}
                                                    type="button"
                                                >
                                                    <ArrowRight className="size-[18px] text-[#0D978B]" />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
