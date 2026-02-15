<?php

namespace App\Services;

use App\Models\AttributeNormalization;
use App\Models\BrandComplianceRule;
use App\Models\VendorSchemaMapping;

class DataStandardizationService
{
    /**
     * Map a vendor CSV row to ERP columns using schema mappings.
     */
    public function mapVendorRow(string $vendor, array $row): array
    {
        $mappings = VendorSchemaMapping::forVendor($vendor)->get();

        if ($mappings->isEmpty()) {
            return $row;
        }

        $mapped = [];

        foreach ($mappings as $mapping) {
            $vendorCol = $mapping->vendor_column;

            if (! array_key_exists($vendorCol, $row)) {
                continue;
            }

            $value = $row[$vendorCol];
            $value = $this->applyTransform($value, $mapping->transform_rule);
            $mapped[$mapping->erp_column] = $value;
        }

        return $mapped;
    }

    /**
     * Normalize an attribute value using the normalization lookup table.
     */
    public function normalizeAttribute(string $type, string $rawValue): string
    {
        $normalization = AttributeNormalization::where('attribute_type', $type)
            ->whereRaw('LOWER(raw_value) = ?', [strtolower($rawValue)])
            ->first();

        return $normalization?->normalized_value ?? $rawValue;
    }

    /**
     * Validate data against brand compliance rules. Returns array of violations.
     */
    public function validateBrandCompliance(string $brand, array $data): array
    {
        $rules = BrandComplianceRule::forBrand($brand)->active()->get();
        $violations = [];

        foreach ($rules as $rule) {
            $ruleViolations = match ($rule->rule_type) {
                'required_field' => $this->checkRequiredFields($rule->rule_config, $data),
                'naming_convention' => $this->checkNamingConvention($rule->rule_config, $data),
                'price_range' => $this->checkPriceRange($rule->rule_config, $data),
                default => [],
            };

            $violations = array_merge($violations, $ruleViolations);
        }

        return $violations;
    }

    private function applyTransform(mixed $value, ?array $rule): mixed
    {
        if (! $rule || ! is_string($value)) {
            return $value;
        }

        return match ($rule['type'] ?? null) {
            'uppercase' => strtoupper($value),
            'trim' => trim($value),
            'date_format' => $this->transformDate($value, $rule['from'] ?? 'm/d/Y', $rule['to'] ?? 'Y-m-d'),
            'multiply' => (float) $value * ($rule['factor'] ?? 1),
            default => $value,
        };
    }

    private function transformDate(string $value, string $from, string $to): string
    {
        $date = \DateTime::createFromFormat($from, $value);

        return $date ? $date->format($to) : $value;
    }

    private function checkRequiredFields(array $config, array $data): array
    {
        $violations = [];
        $fields = $config['fields'] ?? [];

        foreach ($fields as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $violations[] = [
                    'rule_type' => 'required_field',
                    'field' => $field,
                    'message' => "Required field '{$field}' is missing or empty",
                ];
            }
        }

        return $violations;
    }

    private function checkNamingConvention(array $config, array $data): array
    {
        $field = $config['field'] ?? null;
        $pattern = $config['pattern'] ?? null;

        if (! $field || ! $pattern || ! isset($data[$field])) {
            return [];
        }

        if (! preg_match($pattern, $data[$field])) {
            return [[
                'rule_type' => 'naming_convention',
                'field' => $field,
                'message' => "Value '{$data[$field]}' does not match pattern {$pattern}",
            ]];
        }

        return [];
    }

    private function checkPriceRange(array $config, array $data): array
    {
        $violations = [];

        if (isset($data['msrp'])) {
            $msrp = (float) $data['msrp'];
            if (isset($config['min_msrp']) && $msrp < $config['min_msrp']) {
                $violations[] = [
                    'rule_type' => 'price_range',
                    'field' => 'msrp',
                    'message' => "MSRP {$msrp} below minimum {$config['min_msrp']}",
                ];
            }
            if (isset($config['max_msrp']) && $msrp > $config['max_msrp']) {
                $violations[] = [
                    'rule_type' => 'price_range',
                    'field' => 'msrp',
                    'message' => "MSRP {$msrp} above maximum {$config['max_msrp']}",
                ];
            }
        }

        if (isset($data['cost'], $data['msrp'], $config['max_cost_ratio'])) {
            $ratio = (float) $data['cost'] / max((float) $data['msrp'], 0.01);
            if ($ratio > $config['max_cost_ratio']) {
                $violations[] = [
                    'rule_type' => 'price_range',
                    'field' => 'cost',
                    'message' => "Cost/MSRP ratio {$ratio} exceeds max {$config['max_cost_ratio']}",
                ];
            }
        }

        return $violations;
    }
}
