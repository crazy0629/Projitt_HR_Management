'use client';

import { ReactNode, useEffect } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { useSettings } from '@/providers/settings-provider';
import { Footer } from './components/footer';
import { Header } from './components/header';
import { Sidebar } from './components/sidebar';
import { useBasic } from '@/context/BasicContext';
import { getCountry } from '@/api/basic';
import { getDepartment } from '@/api/basic';
import { getDesignation } from '@/api/basic';
import { getEmploymentType } from '@/api/basic';
import { getSkills } from '@/api/basic';

export function Layout({ children }: { children: ReactNode }) {
  const isMobile = useIsMobile();
  const { settings, setOption } = useSettings();
  const { setCountry } = useBasic();
  const { setDepartment } = useBasic();
  const { setDesignation } = useBasic();
  const { setEmploymentType } = useBasic();
  const { setSkills } = useBasic();

  useEffect(() => {
    const bodyClass = document.body.classList;

    if (settings.layouts.demo1.sidebarCollapse) {
      bodyClass.add('sidebar-collapse');
    } else {
      bodyClass.remove('sidebar-collapse');
    }
  }, [settings]); // Runs only on settings update

  useEffect(() => {
    // Set current layout
    setOption('layout', 'demo1');
  }, [setOption]);

  useEffect(() => {
    const bodyClass = document.body.classList;

    // Add a class to the body element
    bodyClass.add('demo1');
    bodyClass.add('sidebar-fixed');
    bodyClass.add('header-fixed');

    const timer = setTimeout(() => {
      bodyClass.add('layout-initialized');
    }, 1000); // 1000 milliseconds

    // Remove the class when the component is unmounted
    return () => {
      bodyClass.remove('demo1');
      bodyClass.remove('sidebar-fixed');
      bodyClass.remove('sidebar-collapse');
      bodyClass.remove('header-fixed');
      bodyClass.remove('layout-initialized');
      clearTimeout(timer);
    };
  }, []); // Runs only once on mount

  useEffect(() => {
    const loadCountry = async () => {
      const response = await getCountry();
      setCountry(response.data.data);
    }
    loadCountry();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Runs only once on mount

  useEffect(() => {
    const loadDepartment = async () => {
      const response = await getDepartment();
      setDepartment(response.data.data);
    }
    loadDepartment();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Runs only once on mount

  useEffect(() => {
    const loadDesignation = async () => {
      const response = await getDesignation();
      setDesignation(response.data.data);
    }
    loadDesignation();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Runs only once on mount
  
  useEffect(() => {
    const loadEmploymentType = async () => {
      const response = await getEmploymentType();
      setEmploymentType(response.data.data);
    }
    loadEmploymentType();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Runs only once on mount

  useEffect(() => {
    const loadSkills = async () => {
      const response = await getSkills();
      setSkills(response.data.data);
    }
    loadSkills();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Runs only once on mount


  return (
    <>
      {!isMobile && <Sidebar />}

      <div className="wrapper w-full flex grow flex-col">
        <Header />

        <main className="grow w-full box-border py-[30px] sm:px-[40px] px-[20px] bg-[#fafafa]" role="content">
          {children}
        </main>
      </div>
    </>
  );
}
