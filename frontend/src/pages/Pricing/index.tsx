import { useState } from 'react';
import {
  Box, Typography, Card, CardContent, Grid, Button, Chip, LinearProgress,
  Alert, Dialog, DialogTitle, DialogContent, DialogActions, Divider,
  ToggleButton, ToggleButtonGroup, Skeleton,
} from '@mui/material';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import StarIcon from '@mui/icons-material/Star';
import TrendingUpIcon from '@mui/icons-material/TrendingUp';
import TrendingDownIcon from '@mui/icons-material/TrendingDown';
import CreditCardIcon from '@mui/icons-material/CreditCard';
import AccountBalanceWalletIcon from '@mui/icons-material/AccountBalanceWallet';
import { useCurrentPlan, usePublicPlans, useComparePlan } from '@/hooks/usePricing';
import { useChangePlan } from '@/hooks/useSubscription';
import { useCheckout } from '@/hooks/usePayment';
import type { Plan } from '@/types';

export default function PricingPage() {
  const { data: currentData, isLoading: loadingCurrent } = useCurrentPlan();
  const { data: plansData, isLoading: loadingPlans } = usePublicPlans();
  const changePlan = useChangePlan();
  const checkout = useCheckout();

  const [billing, setBilling] = useState<'monthly' | 'yearly'>('monthly');
  const [compareSlug, setCompareSlug] = useState<string | null>(null);
  const [confirmDialog, setConfirmDialog] = useState<Plan | null>(null);
  const [paymentLoading, setPaymentLoading] = useState(false);

  const { data: comparisonData } = useComparePlan(compareSlug);

  const current = currentData?.data;
  const plans = plansData?.data ?? [];
  const comparison = comparisonData?.data;

  const handleChangePlan = () => {
    if (!confirmDialog) return;
    // Plan gratuit → changement direct
    changePlan.mutate(confirmDialog.slug, {
      onSuccess: () => {
        setConfirmDialog(null);
        setCompareSlug(null);
      },
    });
  };

  const handlePayment = async (gateway: 'stripe' | 'paypal') => {
    if (!confirmDialog) return;
    setPaymentLoading(true);
    try {
      const res = await checkout.mutateAsync({
        plan_slug: confirmDialog.slug,
        gateway,
        billing_cycle: billing,
      });
      const data = res.data;
      // Redirect to payment gateway
      if (gateway === 'stripe' && data.checkout_url) {
        window.location.href = data.checkout_url;
      } else if (gateway === 'paypal' && data.approval_url) {
        window.location.href = data.approval_url;
      }
    } catch {
      setPaymentLoading(false);
    }
  };

  if (loadingCurrent || loadingPlans) {
    return (
      <Box sx={{ p: 3 }}>
        <Skeleton variant="text" width={300} height={40} />
        <Grid container spacing={3} sx={{ mt: 2 }}>
          {[1, 2, 3].map((i) => (
            <Grid item xs={12} md={4} key={i}>
              <Skeleton variant="rectangular" height={400} sx={{ borderRadius: 2 }} />
            </Grid>
          ))}
        </Grid>
      </Box>
    );
  }

  return (
    <Box sx={{ p: 3 }}>
      {/* Header */}
      <Typography variant="h5" fontWeight="bold" sx={{ mb: 0.5 }}>
        Abonnement & Tarification
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Gérez votre plan et vos quotas d'utilisation.
      </Typography>

      {/* Current Plan Summary */}
      {current?.plan && (
        <Card sx={{ mb: 4, border: '2px solid', borderColor: 'primary.main' }}>
          <CardContent sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
              <Box>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                  <Typography variant="h6" fontWeight="bold">Plan actuel : {current.plan.name}</Typography>
                  <Chip
                    label={current.plan.is_free ? 'Gratuit' : `${billing === 'monthly' ? current.plan.price_monthly : current.plan.price_yearly}€/${billing === 'monthly' ? 'mois' : 'an'}`}
                    color="primary"
                    size="small"
                  />
                </Box>
                <Typography variant="body2" color="text.secondary">
                  Entreprise : {current.company.name}
                </Typography>
              </Box>
            </Box>

            <Grid container spacing={3}>
              {(['vehicles', 'drivers', 'users'] as const).map((key) => {
                const item = current.usage[key];
                const percent = item.max > 0 ? (item.used / item.max) * 100 : 0;
                const labelMap = { vehicles: 'Véhicules', drivers: 'Chauffeurs', users: 'Utilisateurs' };
                return (
                  <Grid item xs={12} sm={4} key={key}>
                    <Box>
                      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                        <Typography variant="body2" fontWeight={600}>{labelMap[key]}</Typography>
                        <Typography variant="body2" color="text.secondary">
                          {item.used} / {item.max >= 999 ? '∞' : item.max}
                        </Typography>
                      </Box>
                      <LinearProgress
                        variant="determinate"
                        value={Math.min(percent, 100)}
                        sx={{
                          height: 8, borderRadius: 4,
                          bgcolor: '#e8ecef',
                          '& .MuiLinearProgress-bar': {
                            bgcolor: percent >= 90 ? 'error.main' : percent >= 70 ? 'warning.main' : 'primary.main',
                          },
                        }}
                      />
                    </Box>
                  </Grid>
                );
              })}
            </Grid>
          </CardContent>
        </Card>
      )}

      {/* Billing Toggle */}
      <Box sx={{ display: 'flex', justifyContent: 'center', mb: 4 }}>
        <ToggleButtonGroup
          value={billing}
          exclusive
          onChange={(_, v) => v && setBilling(v)}
          size="small"
        >
          <ToggleButton value="monthly">Mensuel</ToggleButton>
          <ToggleButton value="yearly">
            Annuel
            <Chip label="Économisez 20%" size="small" color="success" sx={{ ml: 1, height: 20, fontSize: 11 }} />
          </ToggleButton>
        </ToggleButtonGroup>
      </Box>

      {/* Plans Grid */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        {plans.map((plan: Plan) => {
          const isCurrentPlan = current?.plan?.slug === plan.slug;
          const price = billing === 'monthly' ? plan.price_monthly : plan.price_yearly;

          return (
            <Grid item xs={12} md={4} key={plan.uuid}>
              <Card
                sx={{
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  border: plan.is_popular ? '2px solid' : '1px solid',
                  borderColor: plan.is_popular ? 'primary.main' : '#e8ecef',
                  position: 'relative',
                  transition: '0.2s',
                  '&:hover': { boxShadow: 4, transform: 'translateY(-2px)' },
                }}
              >
                {plan.is_popular && (
                  <Chip
                    icon={<StarIcon sx={{ fontSize: 14 }} />}
                    label="Populaire"
                    color="primary"
                    size="small"
                    sx={{ position: 'absolute', top: -12, left: '50%', transform: 'translateX(-50%)' }}
                  />
                )}
                {isCurrentPlan && (
                  <Chip
                    label="Plan actuel"
                    color="success"
                    size="small"
                    sx={{ position: 'absolute', top: -12, right: 12 }}
                  />
                )}

                <CardContent sx={{ p: 3, flex: 1, display: 'flex', flexDirection: 'column' }}>
                  <Typography variant="h6" fontWeight="bold" sx={{ mb: 1 }}>
                    {plan.name}
                  </Typography>

                  <Box sx={{ mb: 2 }}>
                    {plan.is_free ? (
                      <Typography variant="h4" fontWeight="bold" color="primary">Gratuit</Typography>
                    ) : (
                      <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 0.5 }}>
                        <Typography variant="h4" fontWeight="bold" color="primary">
                          {price}€
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          /{billing === 'monthly' ? 'mois' : 'an'}
                        </Typography>
                      </Box>
                    )}
                    {billing === 'yearly' && plan.yearly_saving > 0 && (
                      <Chip label={`-${plan.yearly_saving}%`} size="small" color="success" sx={{ mt: 0.5 }} />
                    )}
                    {plan.trial_days > 0 && (
                      <Typography variant="caption" color="text.secondary" display="block" sx={{ mt: 0.5 }}>
                        {plan.trial_days} jours d'essai gratuit
                      </Typography>
                    )}
                  </Box>

                  <Divider sx={{ mb: 2 }} />

                  {/* Quotas */}
                  <Box sx={{ mb: 2 }}>
                    <Typography variant="caption" fontWeight={600} color="text.secondary" sx={{ mb: 1, display: 'block' }}>
                      QUOTAS
                    </Typography>
                    <Typography variant="body2">{plan.max_vehicles >= 999 ? 'Véhicules illimités' : `${plan.max_vehicles} véhicules`}</Typography>
                    <Typography variant="body2">{plan.max_drivers >= 999 ? 'Chauffeurs illimités' : `${plan.max_drivers} chauffeurs`}</Typography>
                    <Typography variant="body2">{plan.max_users >= 999 ? 'Utilisateurs illimités' : `${plan.max_users} utilisateurs`}</Typography>
                  </Box>

                  {/* Features */}
                  <Box sx={{ flex: 1 }}>
                    {plan.features.map((f) => (
                      <Box key={f} sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
                        <CheckCircleIcon sx={{ fontSize: 16, color: 'success.main' }} />
                        <Typography variant="body2">{f}</Typography>
                      </Box>
                    ))}
                  </Box>

                  {/* Action */}
                  <Box sx={{ mt: 2 }}>
                    {isCurrentPlan ? (
                      <Button fullWidth variant="outlined" disabled>Plan actuel</Button>
                    ) : (
                      <Button
                        fullWidth
                        variant={plan.is_popular ? 'contained' : 'outlined'}
                        onClick={() => {
                          setCompareSlug(plan.slug);
                          setConfirmDialog(plan);
                        }}
                      >
                        {(current?.plan?.sort_order ?? 0) < plan.sort_order ? 'Upgrader' : 'Changer'}
                      </Button>
                    )}
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          );
        })}
      </Grid>

      {/* Comparison / Confirmation Dialog */}
      <Dialog open={!!confirmDialog} onClose={() => setConfirmDialog(null)} maxWidth="sm" fullWidth>
        <DialogTitle>
          Changer de plan
        </DialogTitle>
        <DialogContent>
          {comparison ? (
            <Box>
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
                {comparison.is_upgrade ? (
                  <TrendingUpIcon color="success" />
                ) : (
                  <TrendingDownIcon color="warning" />
                )}
                <Typography variant="body1" fontWeight={600}>
                  {comparison.current_plan?.name ?? 'Aucun'} → {comparison.target_plan.name}
                </Typography>
              </Box>

              {!comparison.can_switch && (
                <Alert severity="error" sx={{ mb: 2 }}>
                  Vous ne pouvez pas passer à ce plan : vos ressources actuelles dépassent les quotas du plan cible.
                </Alert>
              )}

              <Box sx={{ mb: 2 }}>
                <Typography variant="subtitle2" sx={{ mb: 1 }}>Comparaison des quotas</Typography>
                {(['vehicles', 'drivers', 'users'] as const).map((key) => {
                  const item = comparison.usage[key];
                  const labelMap = { vehicles: 'Véhicules', drivers: 'Chauffeurs', users: 'Utilisateurs' };
                  const exceeds = item.used > item.target_max;
                  return (
                    <Box key={key} sx={{ display: 'flex', justifyContent: 'space-between', py: 0.5 }}>
                      <Typography variant="body2">{labelMap[key]}</Typography>
                      <Typography variant="body2" color={exceeds ? 'error' : 'text.secondary'}>
                        {item.used} utilisé(s) — Limite : {item.current_max} → {item.target_max >= 999 ? '∞' : item.target_max}
                      </Typography>
                    </Box>
                  );
                })}
              </Box>

              <Divider sx={{ my: 2 }} />

              <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                <Typography variant="body2">Différence de prix (mensuel)</Typography>
                <Typography variant="body2" fontWeight={600} color={comparison.price_diff.monthly > 0 ? 'error.main' : 'success.main'}>
                  {comparison.price_diff.monthly > 0 ? '+' : ''}{comparison.price_diff.monthly}€/mois
                </Typography>
              </Box>
            </Box>
          ) : (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
              <Typography color="text.secondary">Chargement de la comparaison...</Typography>
            </Box>
          )}
        </DialogContent>
        <DialogActions sx={{ flexDirection: 'column', gap: 1, p: 2 }}>
          {confirmDialog && !confirmDialog.is_free ? (
            <>
              <Typography variant="subtitle2" sx={{ width: '100%', mb: 1 }}>Choisir le mode de paiement :</Typography>
              <Box sx={{ display: 'flex', gap: 2, width: '100%' }}>
                <Button
                  fullWidth
                  variant="contained"
                  startIcon={<CreditCardIcon />}
                  onClick={() => handlePayment('stripe')}
                  disabled={!comparison?.can_switch || paymentLoading}
                  sx={{ bgcolor: '#635bff', '&:hover': { bgcolor: '#4f46e5' } }}
                >
                  Stripe (Carte)
                </Button>
                <Button
                  fullWidth
                  variant="contained"
                  startIcon={<AccountBalanceWalletIcon />}
                  onClick={() => handlePayment('paypal')}
                  disabled={!comparison?.can_switch || paymentLoading}
                  sx={{ bgcolor: '#003087', '&:hover': { bgcolor: '#001f5c' } }}
                >
                  PayPal
                </Button>
              </Box>
              <Button fullWidth onClick={() => { setConfirmDialog(null); setCompareSlug(null); }} sx={{ mt: 1 }}>
                Annuler
              </Button>
            </>
          ) : (
            <Box sx={{ display: 'flex', gap: 2, width: '100%' }}>
              <Button fullWidth onClick={() => { setConfirmDialog(null); setCompareSlug(null); }}>
                Annuler
              </Button>
              <Button
                fullWidth
                variant="contained"
                onClick={handleChangePlan}
                disabled={!comparison?.can_switch || changePlan.isPending}
              >
                {changePlan.isPending ? 'Changement...' : 'Passer au plan gratuit'}
              </Button>
            </Box>
          )}
        </DialogActions>
      </Dialog>

      {changePlan.isError && (
        <Alert severity="error" sx={{ mt: 2 }}>
          {changePlan.error instanceof Error ? changePlan.error.message : 'Erreur lors du changement de plan.'}
        </Alert>
      )}
      {changePlan.isSuccess && (
        <Alert severity="success" sx={{ mt: 2 }}>
          Plan mis à jour avec succès !
        </Alert>
      )}
    </Box>
  );
}
