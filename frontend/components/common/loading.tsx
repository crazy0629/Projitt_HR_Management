export default function Loading() {
    return (
        <div className="w-[100vw] h-[100vh] bg-[#fafafa] flex flex-col items-center justify-center relative overflow-y-auto overflow-x-hidden">
            <img src="/images/logo.png" alt="Loading" className="h-[50px] animate-bounce" />
        </div>
    );
}