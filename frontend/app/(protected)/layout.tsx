'use client';

import { useSession } from '@/context/SessionContext';
import { Layout } from '../components/layouts/layout';
import Loading from '@/components/common/loading';
import { JSX } from 'react';

/**
 * ProtectedLayout component wraps its children with Layout if the user session is loaded.
 * Shows a loading spinner while the session is loading.
 * 
 * @param {Object} props
 * @param {React.ReactNode} props.children - Nested components to render within the layout
 * @returns JSX.Element - Layout with children or Loading component during session load
 */
export default function ProtectedLayout({
    children,
}: {
    children: React.ReactNode;
}): JSX.Element {
    const { loading } = useSession();

    return !loading ? <Layout>{children}</Layout> : <Loading />;
}
