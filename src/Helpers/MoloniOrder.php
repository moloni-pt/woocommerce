<?php

namespace Moloni\Helpers;

use WC_Order;
use WC_Meta_Data;

class MoloniOrder
{
    public static function getLastCreatedDocument(WC_Order $wcOrder): int
    {
        /** @var WC_Meta_Data[] $documents */
        $documents = $wcOrder->get_meta('_moloni_sent', false);
        $documentId = 0;

        if (!empty($documents)) {
            /** Last item in the array is the latest document */
            $data = end($documents)->get_data();

            $documentId = (int)$data['value'];
        }

        return $documentId;
    }

    public static function getAllCreatedDocuments(WC_Order $wcOrder): array
    {
        /** @var WC_Meta_Data[] $documents */
        $documents = $wcOrder->get_meta('_moloni_sent', false);
        $documentIds = [];

        if (!empty($documents) && is_array($documents)) {
            foreach ($documents as $document) {
                $data = $document->get_data();

                $documentIds[] = (int)$data['value'];
            }
        }

        return $documentIds;
    }

    public static function getLastCreatedCreditNote(WC_Order $wcOrder): int
    {
        /** @var WC_Meta_Data[] $creditNotes */
        $creditNotes = $wcOrder->get_meta('_moloni_credit_note', false);
        $documentId = 0;

        if (!empty($creditNotes)) {
            /** Last item in the array is the latest document */
            $data = end($creditNotes)->get_data();

            $documentId = (int)$data['value'];
        }

        return $documentId;
    }

    public static function getAllCreatedCreditNotes(WC_Order $wcOrder): array
    {
        /** @var WC_Meta_Data[] $creditNotes */
        $creditNotes = $wcOrder->get_meta('_moloni_credit_note', false);
        $documentIds = [];

        if (!empty($creditNotes) && is_array($creditNotes)) {
            foreach ($creditNotes as $creditNote) {
                $data = $creditNote->get_data();

                $documentIds[] = (int)$data['value'];
            }
        }

        return $documentIds;
    }
}
