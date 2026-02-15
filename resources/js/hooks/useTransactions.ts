import { useState, useEffect, useCallback } from 'react';
import { Transaction } from '../types/transaction';
import echo from '../echo';

export function useTransactions(initial: Transaction[]) {
    const [transactions, setTransactions] = useState<Transaction[]>(initial);

    useEffect(() => {
        const channel = echo.channel('transactions');

        channel.listen('TransactionCreated', (e: { transaction: Transaction }) => {
            setTransactions(prev => [e.transaction, ...prev]);
        });

        return () => {
            echo.leave('transactions');
        };
    }, []);

    const addTransaction = useCallback((txn: Transaction) => {
        setTransactions(prev => [txn, ...prev]);
    }, []);

    return { transactions, addTransaction };
}
